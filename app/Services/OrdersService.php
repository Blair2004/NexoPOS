<?php

namespace App\Services;

use App\Classes\Currency;
use App\Classes\Hook;
use App\Events\BeforeSaveOrderTaxEvent;
use App\Events\DueOrdersEvent;
use App\Events\OrderAfterCheckPerformedEvent;
use App\Events\OrderAfterCreatedEvent;
use App\Events\OrderAfterDeletedEvent;
use App\Events\OrderAfterInstalmentPaidEvent;
use App\Events\OrderAfterPaymentCreatedEvent;
use App\Events\OrderAfterProductRefundedEvent;
use App\Events\OrderAfterProductStockCheckedEvent;
use App\Events\OrderAfterRefundedEvent;
use App\Events\OrderAfterUpdatedDeliveryStatus;
use App\Events\OrderAfterUpdatedEvent;
use App\Events\OrderAfterUpdatedProcessStatus;
use App\Events\OrderBeforeDeleteEvent;
use App\Events\OrderBeforeDeleteProductEvent;
use App\Events\OrderBeforePaymentCreatedEvent;
use App\Events\OrderCouponAfterCreatedEvent;
use App\Events\OrderCouponBeforeCreatedEvent;
use App\Events\OrderProductAfterComputedEvent;
use App\Events\OrderProductAfterSavedEvent;
use App\Events\OrderProductBeforeSavedEvent;
use App\Events\OrderRefundPaymentAfterCreatedEvent;
use App\Events\OrderVoidedEvent;
use App\Exceptions\NotAllowedException;
use App\Exceptions\NotFoundException;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\CustomerAccountHistory;
use App\Models\CustomerCoupon;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderAddress;
use App\Models\OrderCoupon;
use App\Models\OrderInstalment;
use App\Models\OrderPayment;
use App\Models\OrderProduct;
use App\Models\OrderProductRefund;
use App\Models\OrderRefund;
use App\Models\OrderStorage;
use App\Models\OrderTax;
use App\Models\PaymentType;
use App\Models\Product;
use App\Models\ProductHistory;
use App\Models\ProductSubItem;
use App\Models\ProductUnitQuantity;
use App\Models\Register;
use App\Models\Role;
use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrdersService
{
    public function __construct(
        protected CustomerService $customerService,
        protected ProductService $productService,
        protected UnitService $unitService,
        protected DateService $dateService,
        protected CurrencyService $currencyService,
        protected Options $optionsService,
        protected TaxService $taxService,
        protected ReportService $reportService,
        protected MathService $mathService
    ) {
        // ...
    }

    /**
     * Create an order on NexoPOS.
     *
     * @param  array      $fields
     * @param  Order|null $order  (optional)
     * @return array
     */
    public function create( $fields, ?Order $order = null )
    {
        $isNew = ! $order instanceof Order;

        /**
         * if the order is being edited, we need to
         * keep a reference to the previous order to be able to compare
         * the changes that has been made.
         */
        if ( $order instanceof Order ) {
            $order->load( 'payments' );
            $order->load( 'products' );
            $order->load( 'coupons' );

            $previousOrder = clone $order;
        }

        $customer = $this->__customerIsDefined( $fields );
        $fields[ 'products' ] = $this->__buildOrderProducts( $fields['products'] );

        /**
         * determine the value of the product
         * on the cart and compare it along with the payment made. This will
         * help to prevent partial or quote orders
         *
         * @param float  $total
         * @param float  $totalPayments
         * @param array  $payments
         * @param string $paymentStatus
         */
        extract( $this->__checkOrderPayments( $fields, $order, $customer ) );

        /**
         * We'll now check the attached coupon
         * and determin whether they can be processed.
         */
        $this->__checkAttachedCoupons( $fields );

        /**
         * As no payment might be provided
         * we make sure to build the products only in case the
         * order is just saved as hold, otherwise a check is made on the available stock
         */
        if ( in_array( $paymentStatus, [ 'paid', 'partially_paid', 'unpaid' ] ) ) {
            $fields[ 'products' ] = $this->__checkProductStock( $fields['products'], $order );
        }

        /**
         * check discount validity and throw an
         * error is something is not set correctly.
         */
        $this->__checkDiscountValidity( $fields );

        /**
         * check delivery informations before
         * proceeding
         */
        $fields = $this->__checkAddressesInformations( $fields );

        /**
         * Check if instalments are provided and if they are all
         * valid regarding the total order price
         */
        $this->__checkProvidedInstalments( $fields );

        /**
         * If any other module wants to perform a verification
         * and block processing, they might use this event.
         */
        OrderAfterCheckPerformedEvent::dispatch( $fields, $order );

        /**
         * ------------------------------------------
         *                  WARNING
         * ------------------------------------------
         * All what follow will proceed database
         * modification. All verifications on current order
         * should be made prior this section
         */
        $order = $this->__initOrder( $fields, $paymentStatus, $order );

        /**
         * if we're editing an order. We need to loop the products in order
         * to recover all the products that has been deleted from the POS and therefore
         * aren't tracked no more. This also make stock adjustment for product which has changed.
         */
        $this->__deleteUntrackedProducts( $order, $fields[ 'products' ] );

        $this->__saveAddressInformations( $order, $fields );

        /**
         * if the order has a valid payment
         * method, then we can save that and attach it the ongoing order.
         */
        if ( in_array( $paymentStatus, [
            Order::PAYMENT_PAID,
            Order::PAYMENT_PARTIALLY,
            Order::PAYMENT_UNPAID,
        ] ) ) {
            $this->__saveOrderPayments( $order, $payments, $customer );
        }

        /**
         * save order instalments
         */
        $this->__saveOrderInstalments( $order, $fields[ 'instalments' ] ?? [] );

        /**
         * save order coupons
         */
        $this->__saveOrderCoupons( $order, $fields[ 'coupons' ] ?? [] );

        /**
         * @var Order $order
         * @var float $taxes
         * @var float $subTotal
         */
        extract( $this->__saveOrderProducts( $order, $fields[ 'products' ] ) );

        /**
         * register taxes for the order
         */
        $this->__registerTaxes( $order, $fields[ 'taxes' ] ?? [] );

        /**
         * compute order total
         */
        $this->__computeOrderTotal( compact( 'order', 'subTotal', 'paymentStatus', 'totalPayments' ) );

        $order->save();
        $order->load( 'payments' );
        $order->load( 'products' );
        $order->load( 'coupons' );

        /**
         * let's notify when an
         * new order has been placed
         */
        $isNew ?
            event( new OrderAfterCreatedEvent( $order, $fields ) ) :
            event( new OrderAfterUpdatedEvent(
                newOrder: $order,
                prevOrder: $previousOrder,
                fields: $fields
            ) );

        return [
            'status' => 'success',
            'message' => $isNew ? __( 'The order has been placed.' ) : __( 'The order has been updated' ),
            'data' => compact( 'order' ),
        ];
    }

    /**
     * Will save order installments if
     * it's provider
     *
     * @param  array $instalments
     * @return void
     */
    public function __saveOrderInstalments( Order $order, $instalments = [] )
    {
        if ( ! empty( $instalments ) ) {
            /**
             * delete previous instalments
             */
            $order->instalments->each( fn( $instalment ) => $instalment->delete() );

            $tracked = [];

            foreach ( $instalments as $instalment ) {
                $newInstalment = new OrderInstalment;

                if ( isset( $instalment[ 'paid' ] ) && $instalment[ 'paid' ] ) {
                    $payment = OrderPayment::where( 'order_id', $order->id )
                        ->where( 'value', $instalment[ 'amount' ] )
                        ->whereNotIn( 'id', $tracked )
                        ->first();

                    if ( $payment instanceof OrderPayment ) {
                        /**
                         * We keep a reference to avoid
                         * having to track that twice.
                         */
                        $tracked[] = $payment->id;

                        /**
                         * let's attach the payment
                         * id to the instalment.
                         */
                        $newInstalment->payment_id = $payment->id ?? null;
                    }
                }

                $newInstalment->amount = $instalment[ 'amount' ];
                $newInstalment->order_id = $order->id;
                $newInstalment->paid = $instalment[ 'paid' ] ?? false;
                $newInstalment->date = Carbon::parse( $instalment[ 'date' ] )->toDateTimeString();
                $newInstalment->save();
            }
        }
    }

    /**
     * check if the provided instalments are
     * valid and verify it allong with the order
     * total.
     *
     * @param  array $fields
     * @return void
     *
     * @throws NotAllowedException
     */
    public function __checkProvidedInstalments( $fields )
    {
        if (
            isset( $fields[ 'instalments' ] ) &&
            ! empty( $fields[ 'instalments' ] ) &&
            ! in_array( $fields[ 'payment_status' ] ?? null, [ Order::PAYMENT_HOLD ] )
        ) {
            $instalments = collect( $fields[ 'instalments' ] );
            $customer = Customer::find( $fields[ 'customer_id' ] );

            if ( (float) $customer->group->minimal_credit_payment > 0 ) {
                $minimal = Currency::define( $fields[ 'total' ] )
                    ->multipliedBy( $customer->group->minimal_credit_payment )
                    ->dividedBy( 100 )
                    ->toFloat();

                /**
                 * if the minimal provided
                 * amount thoses match the required amount.
                 */
                if ( $minimal > Currency::raw( $fields[ 'tendered' ] ) && ns()->option->get( 'ns_orders_allow_unpaid' ) === 'no' ) {
                    throw new NotAllowedException(
                        sprintf(
                            __( 'The minimal payment of %s has\'nt been provided.' ),
                            (string) Currency::define( $minimal )
                        )
                    );
                }
            }
        }
    }

    /**
     * Checks whether the attached coupons are valid
     *
     * @param  array $coupons
     * @return void
     */
    public function __checkAttachedCoupons( $fields )
    {
        collect( $fields[ 'coupons' ] ?? [] )->each( function ( $coupon ) use ( $fields ) {
            $result = $this->customerService->checkCouponExistence( $coupon, $fields );
        } );
    }

    /**
     * Computes the total of the provided coupons
     *
     * @param  array $fields
     * @param  float $subtotal
     * @return float
     */
    private function __computeOrderCoupons( $fields, $subtotal )
    {
        if ( isset( $fields[ 'coupons' ] ) ) {
            return collect( $fields[ 'coupons' ] )->map( function ( $coupon ) use ( $subtotal ) {
                if ( ! isset( $coupon[ 'value' ] ) ) {
                    return $this->__computeCouponValue( $coupon, $subtotal );
                }

                return $coupon[ 'value' ];
            } )->sum();
        }

        return 0;
    }

    private function __computeCouponValue( $coupon, $subtotal )
    {
        return match ( $coupon[ 'discount_type' ] ) {
            'percentage_discount' => $this->computeDiscountValues( $coupon[ 'discount_value' ], $subtotal ),
            'flat_discount' => $coupon[ 'discount_value' ]
        };
    }

    /**
     * Save the coupons by attaching them to the processed order
     *
     * @param  array $coupons
     * @return void
     */
    public function __saveOrderCoupons( Order $order, $coupons )
    {
        $savedCoupons = [];

        $order->total_coupons = 0;

        foreach ( $coupons as $arrayCoupon ) {
            $coupon = Coupon::find( $arrayCoupon[ 'coupon_id' ] );

            OrderCouponBeforeCreatedEvent::dispatch( $coupon, $order );

            $existingCoupon = OrderCoupon::where( 'order_id', $order->id )
                ->where( 'coupon_id', $coupon->id )
                ->first();

            $customerCoupon = CustomerCoupon::where( 'coupon_id', $coupon->id )
                ->where( 'customer_id', $order->customer_id )
                ->firstOrFail();

            if ( ! $existingCoupon instanceof OrderCoupon ) {
                $existingCoupon = new OrderCoupon;
                $existingCoupon->order_id = $order->id;
                $existingCoupon->coupon_id = $coupon[ 'id' ];
                $existingCoupon->customer_coupon_id = $customerCoupon->id;
                $existingCoupon->minimum_cart_value = $coupon[ 'minimum_cart_value' ] ?: 0;
                $existingCoupon->maximum_cart_value = $coupon[ 'maximum_cart_value' ] ?: 0;
                $existingCoupon->name = $coupon[ 'name' ] ?: 0;
                $existingCoupon->type = $coupon[ 'type' ] ?: 0;
                $existingCoupon->limit_usage = $coupon[ 'limit_usage' ] ?: 0;
                $existingCoupon->code = $coupon[ 'code' ];
                $existingCoupon->author = $order->author ?? Auth::id();
                $existingCoupon->discount_value = $coupon[ 'discount_value' ] ?: 0;
            }

            $existingCoupon->value = $coupon[ 'value' ] ?: (
                $arrayCoupon[ 'type' ] === 'percentage_discount' ?
                    $this->computeDiscountValues( $arrayCoupon[ 'discount_value' ], $order->subtotal ) :
                    $arrayCoupon[ 'discount_value' ]
            );

            /**
             * that should compute
             * the coupons value automatically
             */
            $order->total_coupons += $existingCoupon->value;

            $existingCoupon->save();

            OrderCouponAfterCreatedEvent::dispatch( $existingCoupon, $order );

            $savedCoupons[] = $existingCoupon->id;
        }

        /**
         * Every coupon that is not processed
         * should be deleted.
         */
        OrderCoupon::where( 'order_id', $order->id )
            ->whereNotIn( 'id', $savedCoupons )
            ->delete();
    }

    /**
     * Will compute the taxes assigned to an order
     */
    public function __saveOrderTaxes( Order $order, $taxes ): float
    {
        /**
         * if previous taxes had been registered,
         * we need to clear them
         */
        OrderTax::where( 'order_id', $order->id )->delete();

        $taxCollection = collect( [] );

        if ( count( $taxes ) > 0 ) {
            $percentages = collect( $taxes )->map( fn( $tax ) => $tax[ 'rate' ] );
            $response = $this->taxService->getTaxesComputed(
                tax_type: $order->tax_type,
                rates: $percentages->toArray(),
                value: ns()->currency->define( $order->subtotal )->subtractBy( $order->discount )->toFloat()
            );

            foreach ( $taxes as $index => $tax ) {
                $orderTax = new OrderTax;
                $orderTax->tax_name = $tax[ 'name' ];
                $orderTax->tax_value = ( $response[ 'percentages' ][ $index ][ 'tax' ] ?? 0 );
                $orderTax->rate = $tax[ 'rate' ];
                $orderTax->tax_id = $tax[ 'tax_id' ];
                $orderTax->order_id = $order->id;

                BeforeSaveOrderTaxEvent::dispatch( $orderTax, $tax );

                $orderTax->save();

                $taxCollection->push( $orderTax );
            }
        }

        /**
         * we'll increase the tax value and
         * update the value on the order tax object
         */
        return $taxCollection->map( fn( $tax ) => $tax->tax_value )->sum();
    }

    /**
     * Assign taxes to the processed order
     *
     * @param  array $taxes
     * @return void
     */
    public function __registerTaxes( Order $order, $taxes )
    {
        switch ( ns()->option->get( 'ns_pos_vat' ) ) {
            case 'products_vat':
                $order->products_tax_value = $this->getOrderProductsTaxes( $order );
                break;
            case 'flat_vat':
            case 'variable_vat':
                $order->tax_value = Currency::define( $this->__saveOrderTaxes( $order, $taxes ) )->toFloat();
                $order->products_tax_value = 0;
                break;
            case 'products_variable_vat':
            case 'products_flat_vat':
                $order->tax_value = Currency::define( $this->__saveOrderTaxes( $order, $taxes ) )->toFloat();
                $order->products_tax_value = $this->getOrderProductsTaxes( $order );
                break;
        }

        $order->total_tax_value = $order->tax_value + $order->products_tax_value;
    }

    /**
     * will delete the products belonging to an order
     * that aren't tracked.
     *
     * @param  Order $order
     * @param  array $products
     * @return void
     */
    public function __deleteUntrackedProducts( $order, $products )
    {
        if ( $order instanceof Order ) {
            $ids = collect( $products )
                ->filter( fn( $product ) => isset( $product[ 'id' ] ) && isset( $product[ 'unit_id' ] ) )
                ->map( fn( $product ) => $product[ 'id' ] . '-' . $product[ 'unit_id' ] )
                ->toArray();

            /**
             * While the order is being edited, we'll check if the new quantity of
             * each product is different from the previous known quantity, to perform
             * adjustment accordingly. In that case we'll use adjustment-return & sale.
             */
            if ( $order->payment_status !== Order::PAYMENT_HOLD ) {
                $order->products->map( function ( OrderProduct $product ) use ( $products ) {
                    $productHistory = ProductHistory::where( 'operation_type', ProductHistory::ACTION_SOLD )
                        ->where( 'order_product_id', $product->id )
                        ->first();

                    /**
                     * We should restore or retreive quantities when the
                     * product has initially be marked as sold.
                     */
                    if ( $productHistory instanceof ProductHistory ) {
                        $products = collect( $products )
                            ->filter( fn( $product ) => isset( $product[ 'id' ] ) )
                            ->mapWithKeys( fn( $product ) => [ $product[ 'id' ] => $product ] )
                            ->toArray();

                        if ( in_array( $product->id, array_keys( $products ) ) ) {
                            if ( $product->quantity < $products[ $product->id ][ 'quantity' ] ) {
                                return [
                                    'operation' => 'add',
                                    'unit_price' => $products[ $product->id ][ 'unit_price' ],
                                    'total_price' => $products[ $product->id ][ 'total_price' ],
                                    'quantity' => $products[ $product->id ][ 'quantity' ] - $product->quantity,
                                    'orderProduct' => $product,
                                ];
                            } elseif ( $product->quantity > $products[ $product->id ][ 'quantity' ] ) {
                                return [
                                    'operation' => 'remove',
                                    'unit_price' => $products[ $product->id ][ 'unit_price' ],
                                    'total_price' => $products[ $product->id ][ 'total_price' ],
                                    'quantity' => $product->quantity - $products[ $product->id ][ 'quantity' ],
                                    'orderProduct' => $product,
                                ];
                            }
                        }
                    }

                    /**
                     * When no changes has been made
                     * on the order products.
                     */
                    return false;
                } )
                    ->filter( fn( $adjustment ) => $adjustment !== false )
                    ->each( function ( $adjustment ) use ( $order ) {
                        if ( $adjustment[ 'operation' ] === 'remove' ) {
                            $adjustment[ 'orderProduct' ]->quantity -= $adjustment[ 'quantity' ];

                            $this->productService->stockAdjustment(
                                ProductHistory::ACTION_ADJUSTMENT_RETURN, [
                                    'unit_id' => $adjustment[ 'orderProduct' ]->unit_id,
                                    'unit_price' => $adjustment[ 'orderProduct' ]->unit_price,
                                    'product_id' => $adjustment[ 'orderProduct' ]->product_id,
                                    'quantity' => $adjustment[ 'quantity' ],
                                    'orderProduct' => $adjustment[ 'orderProduct' ],
                                    'order_id' => $order->id,
                                ]
                            );
                        } else {
                            $adjustment[ 'orderProduct' ]->quantity += $adjustment[ 'quantity' ];

                            $this->productService->stockAdjustment(
                                ProductHistory::ACTION_ADJUSTMENT_SALE, [
                                    'unit_id' => $adjustment[ 'orderProduct' ]->unit_id,
                                    'unit_price' => $adjustment[ 'orderProduct' ]->unit_price,
                                    'product_id' => $adjustment[ 'orderProduct' ]->product_id,
                                    'orderProduct' => $adjustment[ 'orderProduct' ],
                                    'quantity' => $adjustment[ 'quantity' ],
                                    'order_id' => $order->id,
                                ]
                            );
                        }

                        /**
                         * for the product that was already tracked
                         * we'll just update the price and quantity
                         */
                        $adjustment[ 'orderProduct' ]->unit_price = $adjustment[ 'unit_price' ];
                        $adjustment[ 'orderProduct' ]->total_price = $adjustment[ 'total_price' ];
                        $adjustment[ 'orderProduct' ]->save();
                    } );
            }

            /**
             * Every product that is missing when the order is being
             * proceesed another time should be removed. If the order has
             * already affected the stock, we should make some adjustments.
             */
            $order->products->each( function ( $orderProduct ) use ( $ids, $order ) {
                /**
                 * if a product has the unit id changed
                 * the product he considered as new and the old is returned
                 * to the stock.
                 */
                $reference = $orderProduct->id . '-' . $orderProduct->unit_id;

                if ( ! in_array( $reference, $ids ) ) {
                    $orderProduct->delete();

                    /**
                     * If the order has changed the stock. The operation
                     * that update it should affect the stock as well.
                     */
                    if ( $order->payment_status !== Order::PAYMENT_HOLD ) {
                        $this->productService->stockAdjustment(
                            ProductHistory::ACTION_ADJUSTMENT_RETURN, [
                                'unit_id' => $orderProduct->unit_id,
                                'unit_price' => $orderProduct->unit_price,
                                'orderProduct' => $orderProduct,
                                'product_id' => $orderProduct->product_id,
                                'quantity' => $orderProduct->quantity,
                                'order_id' => $order->id,
                            ]
                        );
                    }
                }
            } );
        }
    }

    /**
     * get the current shipping
     * feels
     *
     * @param array fields
     */
    private function __getShippingFee( $fields ): float
    {
        return $this->currencyService->define( $fields['shipping'] ?? 0 )->toFloat();
    }

    /**
     * Check whether a discount is valid or
     * not
     *
     * @param array fields
     * @return void
     *
     * @throws NotAllowedException
     */
    public function __checkDiscountValidity( $fields )
    {
        if ( ! empty( @$fields['discount_type'] ) ) {
            if ( $fields['discount_type'] === 'percentage' && ( floatval( $fields['discount_percentage'] ) < 0 ) || ( floatval( $fields['discount_percentage'] ) > 100 ) ) {
                throw new NotAllowedException( __( 'The percentage discount provided is not valid.' ) );
            } elseif ( $fields['discount_type'] === 'flat' ) {
                $productsTotal = $fields[ 'products' ]->map( function ( $product ) {
                    return $this->currencyService->define( $product['quantity'] )
                        ->multiplyBy( floatval( $product['unit_price'] ) )
                        ->toFloat();
                } )->sum();

                if ( $fields['discount'] > $productsTotal ) {
                    throw new NotAllowedException( __( 'A discount cannot exceed the sub total value of an order.' ) );
                }
            }
        }
    }

    /**
     * Check defined address informations
     * and throw an error if a fields is not supported
     *
     * @param array fields
     * @return array $fields
     */
    private function __checkAddressesInformations( $fields )
    {
        $allowedKeys = [
            'id',
            'first_name',
            'last_name',
            'phone',
            'address_1',
            'address_2',
            'country',
            'city',
            'pobox',
            'company',
            'email',
        ];

        /**
         * this will erase the unsupported
         * attribute before saving the customer addresses.
         */
        if ( ! empty( $fields[ 'addresses' ] ) ) {
            foreach ( ['shipping', 'billing'] as $type ) {
                if ( isset( $fields['addresses'][$type] ) ) {
                    $keys = array_keys( $fields['addresses'][$type] );
                    foreach ( $keys as $key ) {
                        if ( ! in_array( $key, $allowedKeys ) ) {
                            unset( $fields[ 'addresses' ][ $type ][ $key ] );
                        }
                    }
                }
            }
        }

        return $fields;
    }

    /**
     * Save address informations
     * for a specific order
     *
     * @param Order
     * @param array of key=>value fields submitted
     */
    private function __saveAddressInformations( $order, $fields )
    {
        foreach ( ['shipping', 'billing'] as $type ) {
            /**
             * if the id attribute is already provided
             * we should attempt to find the related addresses
             * and use that as a reference otherwise create a new instance.
             *
             * @todo add a verification to enforce address to be attached
             * to the processed order.
             */
            if ( isset( $fields[ 'addresses' ][ $type ][ 'id' ] ) ) {
                $orderShipping = OrderAddress::find( $fields[ 'addresses' ][ $type ][ 'id' ] );
            } else {
                $orderShipping = new OrderAddress;
            }

            $orderShipping->type = $type;

            if ( ! empty( $fields['addresses'][$type] ) ) {
                foreach ( $fields['addresses'][$type] as $key => $value ) {
                    $orderShipping->$key = $value;
                }
            }

            $orderShipping->author = $order->author ?? Auth::id();
            $orderShipping->order_id = $order->id;
            $orderShipping->save();
        }
    }

    private function __saveOrderPayments( $order, $payments, $customer )
    {
        /**
         * As we're about to record new payments,
         * we first need to delete previous payments that
         * might have been made. Probably we'll need to keep these
         * order and only update them.
         */
        foreach ( $payments as $payment ) {
            $this->__saveOrderSinglePayment( $payment, $order );
        }

        $order->tendered = $this->currencyService->define( collect( $payments )->map( fn( $payment ) => floatval( $payment[ 'value' ] ) )->sum() )->toFloat();
    }

    /**
     * Perform a single payment to a provided order
     * and ensure to display relevant events
     *
     * @param  array $payment
     * @return array
     */
    public function makeOrderSinglePayment( $payment, Order $order )
    {
        // Check if the order is already paid
        if ( $order->payment_status === Order::PAYMENT_PAID ) {
            throw new NotAllowedException( __( 'Unable to proceed as the order is already paid.' ) );
        }

        /**
         * We want to prevent making payment on register that are closed.
         * if the cash regsiter feature is enabled.
         */
        if ( ns()->option->get( 'ns_pos_registers_enabled', 'no' ) === 'yes' && isset( $payment[ 'register_id' ] ) ) {
            $register = Register::opened()->where( 'id', $payment[ 'register_id' ] )->count();

            if ( $register === 0 ) {
                throw new NotAllowedException( __( 'Unable to make a payment on a closed cash register.' ) );
            }
        }

        /**
         * We should check if the order allow instalments. This is only done if
         * we've enabled strict instalments.
         */
        if ( $order->instalments->count() > 0 && $order->support_instalments && ns()->option->get( 'ns_orders_strict_instalments', 'no' ) === true ) {
            $paymentToday = $order->instalments()
                ->where( 'paid', false )
                ->where( 'date', '>=', ns()->date->copy()->startOfDay()->toDateTimeString() )
                ->where( 'date', '<=', ns()->date->copy()->endOfDay()->toDateTimeString() )
                ->get();

            if ( $paymentToday->count() === 0 ) {
                throw new NotFoundException( __( 'No payment is expected at the moment. If the customer want to pay early, consider adjusting instalment payments date.' ) );
            }
        }

        $payment = $this->__saveOrderSinglePayment( $payment, $order );

        /**
         * let's refresh the order to check whether the
         * payment has made the order complete or not.
         */
        $order->register_id = $payment[ 'register_id' ];
        $order->save();
        $order->refresh();

        $this->refreshOrder( $order );

        return [
            'status' => 'success',
            'message' => __( 'The payment has been saved.' ),
            'data' => compact( 'payment' ),
        ];
    }

    /**
     * Save an order payment (or update). deplete customer
     * account if "account payment" is used.
     *
     * @param array $payment
     */
    private function __saveOrderSinglePayment( $payment, Order $order ): OrderPayment
    {
        OrderBeforePaymentCreatedEvent::dispatch( $payment, $order->customer );

        $orderPayment = isset( $payment[ 'id' ] ) ? OrderPayment::find( $payment[ 'id' ] ) : false;

        if ( ! $orderPayment instanceof OrderPayment ) {
            $orderPayment = new OrderPayment;
        }

        /**
         * When the customer is making some payment
         * we store it on his history.
         */
        if ( $payment[ 'identifier' ] === OrderPayment::PAYMENT_ACCOUNT ) {
            $this->customerService->saveTransaction(
                $order->customer,
                CustomerAccountHistory::OPERATION_PAYMENT,
                $payment[ 'value' ],
                __( 'Order Payment' ), [
                    'order_id' => $order->id,
                ]
            );
        }

        $orderPayment->order_id = $order->id;
        $orderPayment->identifier = $payment['identifier'];
        $orderPayment->value = $this->currencyService->define( $payment['value'] )->toFloat();
        $orderPayment->author = $order->author ?? Auth::id();
        $orderPayment->save();

        OrderAfterPaymentCreatedEvent::dispatch( $orderPayment, $order );

        return $orderPayment;
    }

    /**
     * Checks the order payements and compare
     * it to the product values and determine
     * if the order can proceed
     *
     * @param Collection $products
     * @param array field
     */
    private function __checkOrderPayments( $fields, ?Order $order, Customer $customer )
    {
        /**
         * we shouldn't process order if while
         * editing an order it seems that order is already paid.
         */
        if ( $order !== null && $order->payment_status === Order::PAYMENT_PAID ) {
            throw new NotAllowedException( __( 'Unable to edit an order that is completely paid.' ) );
        }

        /**
         * if the order was partially paid and we would like to change
         * some product, we need to make sure that the previously submitted
         * payment hasn't been deleted.
         */
        if ( $order instanceof Order ) {
            $paymentIds = collect( $fields[ 'payments' ] ?? [] )
                ->map( fn( $payment ) => $payment[ 'id' ] ?? false )
                ->filter( fn( $payment ) => $payment !== false )
                ->toArray();

            $order->payments->each( function ( $payment ) use ( $paymentIds ) {
                if ( ! in_array( $payment->id, $paymentIds ) ) {
                    throw new NotAllowedException( __( 'Unable to proceed as one of the previous submitted payment is missing from the order.' ) );
                }
            } );

            /**
             * if the order was no more "hold"
             * we shouldn't allow the order to switch to hold.
             */
            if ( $order->payment_status !== Order::PAYMENT_HOLD && isset( $fields[ 'payment_status' ] ) && $fields[ 'payment_status' ] === Order::PAYMENT_HOLD ) {
                throw new NotAllowedException( __( 'The order payment status cannot switch to hold as a payment has already been made on that order.' ) );
            }
        }

        $totalPayments = 0;

        $subtotal = ns()->currency->define( collect( $fields[ 'products' ] )->map( function ( $product ) {
            return floatval( $product['total_price'] );
        } )->sum() )->toFloat();

        $total = $this->currencyService->define(
            $subtotal + $this->__getShippingFee( $fields )
        )
            ->subtractBy( ( $fields[ 'discount' ] ?? $this->computeDiscountValues( $fields[ 'discount_percentage' ] ?? 0, $subtotal ) ) )
            ->subtractBy( $this->__computeOrderCoupons( $fields, $subtotal ) )
            ->toFloat();

        $allowedPaymentsGateways = PaymentType::active()
            ->get()
            ->map( fn( $paymentType ) => $paymentType->identifier )
            ->toArray();

        if ( ! empty( $fields[ 'payments' ] ) ) {
            foreach ( $fields[ 'payments' ] as $payment ) {
                if ( in_array( $payment['identifier'], $allowedPaymentsGateways ) ) {
                    /**
                     * check if the customer account balance is enough for the account-payment
                     * when that payment is provided
                     */
                    if ( $payment[ 'identifier' ] === 'account-payment' && $customer->account_amount < floatval( $payment[ 'value' ] ) ) {
                        throw new NotAllowedException( __( 'The customer account funds are\'nt enough to process the payment.' ) );
                    }

                    $totalPayments = $this->currencyService->define( $totalPayments )
                        ->additionateBy( $payment['value'] )
                        ->get();
                } else {
                    throw new NotAllowedException( __( 'Unable to proceed. One of the submitted payment type is not supported.' ) );
                }
            }
        }

        /**
         * determine if according to the payment
         * we're free to proceed with that
         */
        if ( $totalPayments < $total ) {
            if (
                $this->optionsService->get( 'ns_orders_allow_partial', true ) === false &&
                $totalPayments > 0
            ) {
                throw new NotAllowedException( __( 'Unable to proceed. Partially paid orders aren\'t allowed. This option could be changed on the settings.' ) );
            } elseif (
                $this->optionsService->get( 'ns_orders_allow_incomplete', true ) === false &&
                $totalPayments === 0
            ) {
                throw new NotAllowedException( __( 'Unable to proceed. Unpaid orders aren\'t allowed. This option could be changed on the settings.' ) );
            }

            /**
             * We don't want the customer to be able to exceed a credit limit
             * granted to his account.
             */
            if (
                (float) $customer->credit_limit_amount > 0
                && $totalPayments === 0
                && $fields[ 'payment_status' ] !== Order::PAYMENT_HOLD
                && (float) $customer->credit_limit_amount < (float) $customer->owed_amount + (float) $total ) {
                throw new NotAllowedException( sprintf(
                    __( 'By proceeding this order, the customer will exceed the maximum credit allowed for his account: %s.' ),
                    (string) ns()->currency->fresh( $customer->credit_limit_amount )
                ) );
            }
        }

        if ( $totalPayments >= $total && count( $fields[ 'payments' ] ?? [] ) > 0 ) {
            $paymentStatus = Order::PAYMENT_PAID;
        } elseif ( $totalPayments < $total && $totalPayments > 0 ) {
            $paymentStatus = Order::PAYMENT_PARTIALLY;
        } elseif ( $totalPayments === 0 && ( ! isset( $fields[ 'payment_status' ] ) || ( $fields[ 'payment_status' ] !== Order::PAYMENT_HOLD ) ) ) {
            $paymentStatus = Order::PAYMENT_UNPAID;
        } elseif ( $totalPayments === 0 && ( isset( $fields[ 'payment_status' ] ) && ( $fields[ 'payment_status' ] === Order::PAYMENT_HOLD ) ) ) {
            $paymentStatus = Order::PAYMENT_HOLD;
        }

        /**
         * Ultimately, we'll check when a payment is provided
         * the logged user must have the rights to perform a payment
         */
        if ( $totalPayments > 0 ) {
            ns()->restrict( 'nexopos.make-payment.orders', __( 'You\'re not allowed to make payments.' ) );
        }

        return [
            'payments' => $fields['payments'] ?? [],
            'total' => $total,
            'totalPayments' => $totalPayments,
            'paymentStatus' => $paymentStatus,
        ];
    }

    /**
     * Compute an order total based
     * on provided data
     *
     * @param  array $data
     * @return array $order
     */
    protected function __computeOrderTotal( $data )
    {
        /**
         * @param float  $order
         * @param float  $subTotal
         * @param float  $totalPayments
         * @param string $paymentStatus
         */
        extract( $data );

        /**
         * increase the total with the
         * shipping fees and subtract the discounts
         */
        $order->total = Currency::fresh( $order->subtotal )
            ->additionateBy( $order->shipping )
            ->additionateBy(
                ( $order->tax_type === 'exclusive' ? $order->tax_value : 0 )
            )
            ->subtractBy(
                Currency::fresh( $order->total_coupons )
                    ->additionateBy( $order->discount )
                    ->toFloat()
            )
            ->toFloat();

        $order->total_with_tax = $order->total;

        /**
         * compute change
         */
        $order->change = Currency::fresh( $order->tendered )
            ->subtractBy( $order->total )
            ->toFloat();

        /**
         * Compute total with tax.
         *
         * @todo not accurate
         */
        $order->total_without_tax = Currency::fresh( $order->subtotal )
            ->subtractBy( $order->discount )
            ->subtractBy( $order->total_coupons )
            ->subtractBy( $order->tax_value )
            ->toFloat();

        return $order;
    }

    /**
     * @param Order order instance
     * @param array<OrderProduct> array of products
     * @return array [$total, $taxes, $order]
     */
    private function __saveOrderProducts( $order, $products )
    {
        $subTotal = 0;
        $taxes = 0;
        $gross = 0;

        $orderProducts = $products->map( function ( $product ) use ( &$subTotal, &$taxes, &$order, &$gross ) {
            /**
             * if the product id is provided
             * then we can use that id as a reference.
             */
            if ( isset( $product[ 'id' ] ) ) {
                $orderProduct = OrderProduct::find( $product[ 'id' ] );
            } else {
                $orderProduct = new OrderProduct;
            }

            $orderProduct->load( 'product' );

            /**
             * this can be useful to allow injecting
             * data that can later on be compted.
             */
            OrderProductBeforeSavedEvent::dispatch( $orderProduct, $product );

            /**
             * We'll retreive the unit used for
             * the order product.
             *
             * @var Unit $unit
             */
            $unit = Unit::find( $product[ 'unit_id' ] );

            $orderProduct->order_id = $order->id;
            $orderProduct->unit_quantity_id = $product[ 'unit_quantity_id' ];
            $orderProduct->unit_name = $product[ 'unit_name' ] ?? $unit->name;
            $orderProduct->unit_id = $product[ 'unit_id' ];
            $orderProduct->mode = $product[ 'mode' ] ?? 'normal';
            $orderProduct->product_type = $product[ 'product_type' ] ?? 'product';
            $orderProduct->rate = $product[ 'rate' ] ?? 0;
            $orderProduct->product_id = $product[ 'product' ]->id ?? 0;
            $orderProduct->product_category_id = $product[ 'product' ]->category_id ?? 0;
            $orderProduct->name = $product[ 'product' ]->name ?? $product[ 'name' ] ?? __( 'Unnamed Product' );
            $orderProduct->quantity = $product[ 'quantity' ];
            $orderProduct->price_with_tax = $product[ 'price_with_tax' ] ?? 0;
            $orderProduct->price_without_tax = $product[ 'price_without_tax' ] ?? 0;

            /**
             * We might need to have another consideration
             * on how we do compute the taxes
             */
            if ( $product[ 'product' ] instanceof Product && $product[ 'product' ]->tax_type !== 'disabled' && ! empty( $product[ 'product' ]->tax_group_id ) ) {
                $orderProduct->tax_group_id = $product[ 'product' ]->tax_group_id;
                $orderProduct->tax_type = $product[ 'product' ]->tax_type ?? 'inclusive';
                $orderProduct->tax_value = $product[ 'tax_value' ];
            } elseif ( isset( $product[ 'tax_type' ] ) && isset( $product[ 'tax_group_id' ] ) ) {
                $orderProduct->tax_group_id = $product[ 'tax_group_id' ];
                $orderProduct->tax_type = $product[ 'tax_type' ];
                $orderProduct->tax_value = $product[ 'tax_value' ];
            } else {
                $orderProduct->tax_group_id = 0;
                $orderProduct->tax_type = 'disabled';
                $orderProduct->tax_value = 0;
            }

            /**
             * @todo we need to solve the issue with the
             * gross price and determine where we should pull it.
             */
            $orderProduct->unit_price = $this->currencyService->define( $product[ 'unit_price' ] )->toFloat();
            $orderProduct->discount_type = $product[ 'discount_type' ] ?? 'none';
            $orderProduct->discount = $product[ 'discount' ] ?? 0;
            $orderProduct->discount_percentage = $product[ 'discount_percentage' ] ?? 0;
            $orderProduct->total_purchase_price = 0;

            if ( $product[ 'product' ] instanceof Product ) {
                $orderProduct->total_purchase_price = $this->currencyService->define(
                    $product[ 'total_purchase_price' ] ?? Currency::fresh( $this->productService->getCogs(
                        product: $product[ 'product' ],
                        unit: $unit
                    ) )
                        ->multipliedBy( $product[ 'quantity' ] )
                        ->toFloat()
                )
                    ->toFloat();
            }

            $this->computeOrderProduct( $orderProduct );

            $orderProduct->save();

            $subTotal = $this->currencyService->define( $subTotal )
                ->additionateBy( $orderProduct->total_price )
                ->get();

            if (
                in_array( $order[ 'payment_status' ], [ Order::PAYMENT_PAID, Order::PAYMENT_PARTIALLY, Order::PAYMENT_UNPAID ] ) &&
                $product[ 'product' ] instanceof Product
            ) {
                /**
                 * storing the product
                 * history as a sale
                 */
                $history = [
                    'order_id' => $order->id,
                    'unit_id' => $product[ 'unit_id' ],
                    'product_id' => $product[ 'product' ]->id,
                    'quantity' => $product[ 'quantity' ],
                    'unit_price' => $orderProduct->unit_price,
                    'orderProduct' => $orderProduct,
                    'total_price' => $orderProduct->total_price,
                ];

                /**
                 * __deleteUntrackedProducts will delete all products that
                 * already exists and which are edited. We'll here only records
                 * products that doesn't exists yet.
                 */
                $stockHistoryExists = ProductHistory::where( 'order_product_id', $orderProduct->id )
                    ->where( 'operation_type', ProductHistory::ACTION_SOLD )
                    ->count() === 1;

                if ( ! $stockHistoryExists ) {
                    $this->productService->stockAdjustment( ProductHistory::ACTION_SOLD, $history );
                }
            }

            event( new OrderProductAfterSavedEvent( $orderProduct, $order, $product ) );

            return $orderProduct;
        } );

        $order->subtotal = $subTotal;

        return compact( 'subTotal', 'order', 'orderProducts' );
    }

    private function __buildOrderProducts( $products )
    {
        return collect( $products )->map( function ( $orderProduct ) {
            /**
             * by default, we'll assume a quick
             * product is being created.
             */
            $product = null;
            $productUnitQuantity = null;

            if ( ! empty( $orderProduct[ 'sku' ] ?? null ) || ! empty( $orderProduct[ 'product_id' ] ?? null ) ) {
                $product = Cache::remember( 'store-' . ( $orderProduct['product_id'] ?? $orderProduct['sku'] ), 60, function () use ( $orderProduct ) {
                    if ( ! empty( $orderProduct['product_id'] ?? null ) ) {
                        return $this->productService->get( $orderProduct['product_id'] );
                    } elseif ( ! empty( $orderProduct['sku'] ?? null ) ) {
                        return $this->productService->getProductUsingSKUOrFail( $orderProduct['sku'] );
                    }
                } );

                $productUnitQuantity = ProductUnitQuantity::findOrFail( $orderProduct[ 'unit_quantity_id' ] );
            }

            $orderProduct = $this->__buildOrderProduct(
                $orderProduct,
                $productUnitQuantity,
                $product
            );

            return $orderProduct;
        } );
    }

    /**
     * @return SupportCollection $items
     */
    private function __checkProductStock( SupportCollection $items, ?Order $order = null )
    {
        $session_identifier = Str::random( '10' );

        /**
         * here comes a loop.
         * We'll been fetching from the database
         * we need somehow to integrate a cache
         * we'll also populate the unit for the item
         * so that it can be reused
         */
        $items = $items->map( function ( array $orderProduct ) use ( $session_identifier ) {
            if ( $orderProduct[ 'product' ] instanceof Product ) {
                /**
                 * Checking inventory for the grouped products,
                 * by loading all the subitems and multiplying the quantity
                 * with the order quantity.
                 */
                if ( $orderProduct[ 'product' ]->type === Product::TYPE_GROUPED ) {
                    $orderProduct[ 'product' ]->load( 'sub_items.product' );
                    $orderProduct[ 'product' ]
                        ->sub_items
                        ->each( function ( ProductSubItem $subitem ) use ( $session_identifier, $orderProduct ) {
                            /**
                             * Stock management should be enabled
                             * for the sub item.
                             */
                            if ( $subitem->product->stock_management === Product::STOCK_MANAGEMENT_ENABLED ) {
                                /**
                                 * We need a fake orderProduct
                                 * that will have necessary attributes for verification.
                                 */
                                $parentUnit = $this->unitService->get( $orderProduct[ 'unit_id' ] );

                                /**
                                 * computing the exact quantity that will be pulled
                                 * from the actual product inventory.
                                 */
                                $quantity = $this->productService->computeSubItemQuantity(
                                    subItemQuantity: (float) $subitem->quantity,
                                    parentUnit: $parentUnit,
                                    parentQuantity: $orderProduct[ 'quantity' ]
                                );

                                $newFakeOrderProduct = new OrderProduct;
                                $newFakeOrderProduct->quantity = $quantity;
                                $newFakeOrderProduct->unit_quantity_id = $subitem->unit_quantity_id;

                                $this->checkQuantityAvailability(
                                    product: $subitem->product,
                                    productUnitQuantity: $subitem->unit_quantity,
                                    orderProduct: $newFakeOrderProduct->toArray(),
                                    session_identifier: $session_identifier
                                );
                            }
                        } );
                } else {
                    $this->checkQuantityAvailability(
                        product: $orderProduct[ 'product' ],
                        productUnitQuantity: $orderProduct[ 'unitQuantity' ],
                        orderProduct: $orderProduct,
                        session_identifier: $session_identifier
                    );
                }
            }

            return $orderProduct;
        } );

        OrderAfterProductStockCheckedEvent::dispatch( $items, $session_identifier );

        return $items;
    }

    /**
     * Prebuild a product that will be processed
     *
     * @param array Order Product
     * @return array Order Product (updated)
     */
    public function __buildOrderProduct( array $orderProduct, ?ProductUnitQuantity $productUnitQuantity = null, ?Product $product = null )
    {
        /**
         * This will calculate the product default field
         * when they aren't provided.
         */
        $orderProduct = $this->computeProduct( $orderProduct, $product, $productUnitQuantity );
        $orderProduct[ 'unit_id' ] = $productUnitQuantity->unit->id ?? $orderProduct[ 'unit_id' ] ?? 0;
        $orderProduct[ 'unit_quantity_id' ] = $productUnitQuantity->id ?? 0;
        $orderProduct[ 'product' ] = $product;
        $orderProduct[ 'mode' ] = $orderProduct[ 'mode' ] ?? 'normal';
        $orderProduct[ 'product_type' ] = $orderProduct[ 'product_type' ] ?? 'product';
        $orderProduct[ 'rate' ] = $orderProduct[ 'rate' ] ?? 0;
        $orderProduct[ 'unitQuantity' ] = $productUnitQuantity;

        return $orderProduct;
    }

    public function checkQuantityAvailability( $product, $productUnitQuantity, $orderProduct, $session_identifier )
    {
        if ( $product->stock_management === Product::STOCK_MANAGEMENT_ENABLED ) {
            /**
             * What we're doing here
             * 1 - Get the unit assigned to the product being sold
             * 2 - check if the units assigned is what has been stored on the product
             * 3 - If the a group is assigned to a product, the we check if that unit belongs to the unit group
             */
            try {
                $storageQuantity = OrderStorage::withIdentifier( $session_identifier )
                    ->withProduct( $product->id )
                    ->withUnitQuantity( $orderProduct[ 'unit_quantity_id' ] )
                    ->sum( 'quantity' );

                $orderProductQuantity = $orderProduct[ 'quantity' ];

                /**
                 * If the orderProduct has an id, that means we're editing an order. In order to extract what is the exact quantity
                 * we want to deduct from the stock, we need to know wether the quantity has changed (greater or not). We then need to pull
                 * the original reference of the orderProduct to compute the new quantity that will be deducted from inventory.
                 */
                $quantityToIgnore = 0;

                if ( isset( $orderProduct[ 'id' ] ) ) {
                    $stockWasDeducted = ProductHistory::where( 'order_product_id', $orderProduct[ 'id' ] )
                        ->where( 'operation_type', ProductHistory::ACTION_SOLD )
                        ->count() === 1;

                    /**
                     * Only when the stock was deducted, we'll ignore the quantity
                     * that is currently in use by the order product.
                     */
                    if ( $stockWasDeducted ) {
                        $orderProductRerefence = OrderProduct::find( $orderProduct[ 'id' ] );
                        $quantityToIgnore = $orderProductRerefence->quantity;
                    }
                }

                if ( ( $productUnitQuantity->quantity + $quantityToIgnore ) - $storageQuantity < abs( $orderProductQuantity ) ) {
                    throw new \Exception(
                        sprintf(
                            __( 'Unable to proceed, there is not enough stock for %s using the unit %s. Requested : %s, available %s' ),
                            $product->name,
                            $productUnitQuantity->unit->name,
                            abs( $orderProductQuantity ),
                            $productUnitQuantity->quantity - $storageQuantity
                        )
                    );
                }

                /**
                 * We keep reference on the database
                 * that's more easier.
                 */
                $storage = new OrderStorage;
                $storage->product_id = $product->id;
                $storage->unit_id = $productUnitQuantity->unit->id;
                $storage->unit_quantity_id = $orderProduct[ 'unit_quantity_id' ];
                $storage->quantity = $orderProduct[ 'quantity' ];
                $storage->session_identifier = $session_identifier;
                $storage->save();
            } catch ( NotFoundException $exception ) {
                throw new \Exception(
                    sprintf(
                        __( 'Unable to proceed, the product "%s" has a unit which cannot be retreived. It might have been deleted.' ),
                        $product->name
                    )
                );
            }
        }
    }

    public function computeProduct( $fields, ?Product $product = null, ?ProductUnitQuantity $productUnitQuantity = null )
    {
        $sale_price = ( $fields[ 'unit_price' ] ?? $productUnitQuantity->sale_price );

        /**
         * if the discount value wasn't provided, it would have
         * been calculated based on the "discount_percentage" & "discount_type"
         * informations.
         */
        if (
            isset( $fields[ 'discount_percentage' ] ) &&
            isset( $fields[ 'discount_type' ] ) &&
            $fields[ 'discount_type' ] === 'percentage' ) {

            $fields[ 'discount' ] = ( $fields[ 'discount' ] ??
                ns()->currency->define(
                    ns()->currency->define(
                        ns()->currency->define( $sale_price )->multiplyBy( $fields[ 'discount_percentage' ] )->toFloat()
                    )->dividedBy( 100 )->toFloat()
                )->multiplyBy( $fields[ 'quantity' ] )->toFloat() );
        } else {
            $fields[ 'discount' ] = $fields[ 'discount' ] ?? 0;
        }

        /**
         * if the item is assigned to a tax group
         * it should compute the tax otherwise
         * the value is "0".
         */
        if ( empty( $fields[ 'tax_value' ] ) ) {
            $fields[ 'tax_value' ] = $this->currencyService->define(
                $this->taxService->getComputedTaxGroupValue(
                    tax_type: $fields[ 'tax_type' ] ?? $product->tax_type ?? null,
                    tax_group_id: $fields[ 'tax_group_id' ] ?? $product->tax_group_id ?? null,
                    price: $sale_price
                )
            )
                ->multiplyBy( floatval( $fields[ 'quantity' ] ) )
                ->toFloat();
        }

        /**
         * If the total_price is not defined
         * let's compute that
         */
        if ( empty( $fields[ 'total_price' ] ) ) {
            $fields[ 'total_price' ] = (
                $sale_price * floatval( $fields[ 'quantity' ] )
            ) - $fields[ 'discount' ];
        }

        return $fields;
    }

    /**
     * @todo we need to be able to
     * change the code format
     */
    public function generateOrderCode( $order )
    {
        $now = Carbon::parse( $order->created_at );
        $today = $now->toDateString();
        $count = DB::table( 'nexopos_orders_count' )
            ->where( 'date', $today )
            ->value( 'count' );

        if ( $count === null ) {
            $count = 1;
            DB::table( 'nexopos_orders_count' )
                ->insert( [
                    'date' => $today,
                    'count' => $count,
                ] );
        }

        DB::table( 'nexopos_orders_count' )
            ->where( 'date', $today )
            ->increment( 'count' );

        return $now->format( 'y' ) . $now->format( 'm' ) . $now->format( 'd' ) . '-' . str_pad( $count, 3, 0, STR_PAD_LEFT );
    }

    protected function __initOrder( $fields, $paymentStatus, $order )
    {
        /**
         * if the order is not provided as a parameter
         * a new instance is initialized.
         */
        if ( ! $order instanceof Order ) {
            $order = new Order;

            /**
             * if the order has just been created
             * then we'll define the "created_at" column.
             */
            $order->created_at = $fields[ 'created_at' ] ?? ns()->date->getNow()->toDateTimeString();
        }

        /**
         * If any other attributes needs to be
         * saved while creating the order, it should be
         * explicitly allowed on this filter
         */
        foreach ( Hook::filter( 'ns-order-attributes', [] ) as $attribute ) {
            if ( ! in_array( $attribute, [
                'id',
            ] ) ) {
                $order->$attribute = $fields[ $attribute ] ?? null;
            }
        }

        /**
         * let's save the order at
         * his initial state
         */
        $order->customer_id = $fields['customer_id'];
        $order->shipping = $this->currencyService->define( $fields[ 'shipping' ] ?? 0 )->toFloat(); // if shipping is not provided, we assume it's free
        $order->subtotal = $this->currencyService->define( $fields[ 'subtotal' ] ?? 0 )->toFloat() ?: $this->computeSubTotal( $fields, $order );
        $order->discount_type = $fields['discount_type'] ?? null;
        $order->discount_percentage = $this->currencyService->define( $fields['discount_percentage'] ?? 0 )->toFloat();
        $order->discount = (
            $this->currencyService->define( $order->discount_type === 'flat' && isset( $fields['discount'] ) ? $fields['discount'] : 0 )->toFloat()
        ) ?: ( $order->discount_type === 'percentage' ? $this->computeOrderDiscount( $order, $fields ) : 0 );
        $order->total = $this->currencyService->define( $fields[ 'total' ] ?? 0 )->toFloat() ?: $this->computeTotal( $fields, $order );
        $order->type = $fields['type']['identifier'];
        $order->final_payment_date = isset( $fields['final_payment_date' ] ) ? Carbon::parse( $fields['final_payment_date' ] )->format( 'Y-m-d h:m:s' ) : null; // when the order is not saved as laid away
        $order->total_instalments = $fields[ 'total_instalments' ] ?? 0;
        $order->register_id = $fields[ 'register_id' ] ?? null;
        $order->note = $fields[ 'note'] ?? null;
        $order->note_visibility = $fields[ 'note_visibility' ] ?? null;
        $order->updated_at = isset( $fields[ 'updated_at' ] ) ? Carbon::parse( $fields[ 'updated_at' ] )->format( 'Y-m-d h:m:s' ) : ns()->date->getNow()->toDateTimeString();
        $order->tax_group_id = $fields['tax_group_id' ] ?? null;
        $order->tax_type = $fields['tax_type' ] ?? null;
        $order->total_coupons = $fields['total_coupons'] ?? 0;
        $order->payment_status = $paymentStatus;
        $order->delivery_status = 'pending';
        $order->process_status = 'pending';
        $order->support_instalments = $fields[ 'support_instalments' ] ?? true; // by default instalments are supported
        $order->author = $fields[ 'author' ] ?? Auth::id(); // the author can now be changed
        $order->title = $fields[ 'title' ] ?? null;
        $order->tax_value = $this->currencyService->define( $fields[ 'tax_value' ] ?? 0 )->toFloat();
        $order->products_tax_value = $this->currencyService->define( $fields[ 'products_tax_value' ] ?? 0 )->toFloat();
        $order->total_tax_value = $this->currencyService->define( $fields[ 'total_tax_value' ] ?? 0 )->toFloat();
        $order->code = $order->code ?: ''; // to avoid generating a new code
        $order->save();

        if ( $order->code === '' ) {
            $order->code = $this->generateOrderCode( $order ); // to avoid generating a new code
        }

        /**
         * Some order needs to have their
         * delivery and process status updated
         * according to the order type
         */
        $this->updateDeliveryStatus( $order );
        $this->updateProcessStatus( $order );

        $order->save();

        return $order;
    }

    /**
     * Will update the prodcess status of an order
     *
     * @return void
     */
    public function updateProcessStatus( Order $order )
    {
        if ( in_array( $order->type, [ 'delivery', 'takeaway' ] ) ) {
            if ( $order->type === 'delivery' ) {
                $order->process_status = 'pending';
            } else {
                $order->process_status = 'not-available';
            }
        }

        OrderAfterUpdatedProcessStatus::dispatch( $order );
    }

    /**
     * Will order the delivery status of an order
     *
     * @return void
     */
    public function updateDeliveryStatus( Order $order )
    {
        if ( in_array( $order->type, [ 'delivery', 'takeaway' ] ) ) {
            if ( $order->type === 'delivery' ) {
                $order->delivery_status = 'pending';
            } else {
                $order->delivery_status = 'not-available';
            }
        }

        OrderAfterUpdatedDeliveryStatus::dispatch( $order );
    }

    /**
     * Compute the discount data
     *
     * @param  array $fields
     * @return int   $discount
     */
    public function computeOrderDiscount( $order, $fields = [] )
    {
        $fields[ 'discount_type' ] = $fields[ 'discount_type' ] ?? $order->discount_type;
        $fields[ 'discount_percentage' ] = $fields[ 'discount_percentage' ] ?? $order->discount_percentage;
        $fields[ 'discount' ] = $fields[ 'discount' ] ?? $order->discount;
        $fields[ 'subtotal' ] = $fields[ 'subtotal' ] ?? $order->subtotal;
        $fields[ 'discount' ] = $fields[ 'discount' ] ?? $order->discount ?? 0;

        if ( ! empty( $fields[ 'discount_type' ] ) && ! empty( $fields[ 'discount_percentage' ] ) && $fields[ 'discount_type' ] === 'percentage' ) {
            return $this->currencyService->define(
                ns()->currency->define( $fields[ 'subtotal' ] )->multiplyBy( $fields[ 'discount_percentage' ] )->toFloat()
            )->dividedBy( 100 )->toFloat();
        } else {
            return $this->currencyService->define( $fields[ 'discount' ] )->toFloat();
        }
    }

    /**
     * Will compute a tax value using
     * the taxes assigned to an order
     *
     * @param  float  $value
     * @param  string $type
     * @return float  value
     */
    public function computeTaxFromOrderTaxes( Order $order, $value, $type = 'inclusive' )
    {
        return $order->taxes->map( function ( $tax ) use ( $value, $type ) {
            $result = $this->taxService->getVatValue(
                $type, $tax->rate, $value
            );

            return $result;
        } )->sum();
    }

    /**
     * will compute the taxes based
     * on the configuration and the products
     */
    public function computeOrderTaxes( Order $order )
    {
        $posVat = ns()->option->get( 'ns_pos_vat' );
        $taxValue = 0;

        if ( in_array( $posVat, [
            'products_vat',
            'products_flat_vat',
            'products_variable_vat',
        ] ) ) {
            $taxValue = $order
                ->products()
                ->validProducts()
                ->get()
                ->map( function ( $product ) {
                    return floatval( $product->tax_value );
                } )->sum();
        } elseif ( in_array( $posVat, [
            'flat_vat',
            'variable_vat',
        ] ) && $order->taxes->count() > 0 ) {
            $subTotal = $order->products()
                ->validProducts()
                ->sum( 'total_price' );

            $response = $this->taxService->getTaxesComputed(
                tax_type: $order->tax_type,
                rates: $order->taxes->map( fn( $tax ) => $tax->rate )->toArray(),
                value: $subTotal
            );

            $taxValue = $order->taxes->map( function ( $tax, $index ) use ( $response ) {
                $tax->tax_value = $response[ 'percentages' ][ $index ][ 'tax' ];
                $tax->save();

                return $tax->tax_value;
            } )->sum();
        }

        $order->tax_value = $taxValue;
    }

    /**
     * return the tax value for the products
     *
     * @param  array $fields
     * @param  Order $order
     * @return float
     */
    public function getOrderProductsTaxes( $order )
    {
        return $this->currencyService->define( $order
            ->products()
            ->get()
            ->map( fn( $product ) => $product->tax_value )->sum()
        )->toFloat();
    }

    public function computeTotal( $fields, $order )
    {
        return $this->currencyService->define( $order->subtotal )
            ->subtractBy( $order->discount )
            ->additionateBy( $order->shipping )
            ->toFloat();
    }

    public function computeSubTotal( $fields, $order )
    {
        return $this->currencyService->define(
            collect( $fields[ 'products' ] )
                ->map( fn( $product ) => floatval( $product[ 'total_price' ] ) )
                ->sum()
        )
            ->toFloat();
    }

    private function __customerIsDefined( $fields )
    {
        try {
            return $this->customerService->get( $fields['customer_id'] );
        } catch ( NotFoundException $exception ) {
            throw new NotFoundException( __( 'Unable to find the customer using the provided ID. The order creation has failed.' ) );
        }
    }

    public function refundOrder( Order $order, $fields )
    {
        if ( ! in_array( $order->payment_status, [
            Order::PAYMENT_PARTIALLY,
            Order::PAYMENT_UNPAID,
            Order::PAYMENT_PAID,
            Order::PAYMENT_PARTIALLY_REFUNDED,
        ] ) ) {
            throw new NotAllowedException( __( 'Unable to proceed a refund on an unpaid order.' ) );
        }

        $orderRefund = new OrderRefund;
        $orderRefund->author = Auth::id();
        $orderRefund->order_id = $order->id;
        $orderRefund->payment_method = $fields[ 'payment' ][ 'identifier' ];
        $orderRefund->shipping = ( isset( $fields[ 'refund_shipping' ] ) && $fields[ 'refund_shipping' ] ? $order->shipping : 0 );
        $orderRefund->total = 0;
        $orderRefund->save();

        OrderRefundPaymentAfterCreatedEvent::dispatch( $orderRefund );

        $results = [];

        foreach ( $fields[ 'products' ] as $product ) {
            $results[] = $this->refundSingleProduct( $order, $orderRefund, OrderProduct::find( $product[ 'id' ] ), $product );
        }

        /**
         * if the shipping is refunded
         * We'll do that here
         */
        $shipping = 0;

        if ( isset( $fields[ 'refund_shipping' ] ) && $fields[ 'refund_shipping' ] === true ) {
            $shipping = $order->shipping;
            $order->shipping = 0;
        }

        $taxValue = collect( $results )->map( function ( $result ) {
            $refundProduct = $result[ 'data' ][ 'productRefund' ];

            return $refundProduct->tax_value;
        } )->sum() ?: 0;

        $orderRefund->tax_value = ns()->currency->define( $taxValue )->toFloat();

        /**
         * let's update the order refund total
         */
        $orderRefund->load( 'refunded_products' );
        $orderRefund->total = Currency::define(
            $orderRefund->refunded_products->sum( 'total_price' )
        )->additionateBy( $shipping )->toFloat();

        $orderRefund->save();

        /**
         * check if the payment used is the customer account
         * so that we can withdraw the funds to the account
         */
        if ( $fields[ 'payment' ][ 'identifier' ] === OrderPayment::PAYMENT_ACCOUNT ) {
            $this->customerService->saveTransaction(
                $order->customer,
                CustomerAccountHistory::OPERATION_REFUND,
                $fields[ 'total' ],
                __( 'The current credit has been issued from a refund.' ), [
                    'order_id' => $order->id,
                ]
            );
        }

        OrderAfterRefundedEvent::dispatch( $order, $orderRefund );

        return [
            'status' => 'success',
            'message' => __( 'The order has been successfully refunded.' ),
            'data' => compact( 'results', 'order', 'orderRefund' ),
        ];
    }

    /**
     * Refund a single product from an order.
     */
    public function refundSingleProduct( Order $order, OrderRefund $orderRefund, OrderProduct $orderProduct, array $details ): array
    {
        if ( ! in_array( $details[ 'condition' ], [
            OrderProductRefund::CONDITION_DAMAGED,
            OrderProductRefund::CONDITION_UNSPOILED,
        ] ) ) {
            throw new NotAllowedException( __( 'unable to proceed to a refund as the provided status is not supported.' ) );
        }

        if ( ! in_array( $order->payment_status, [
            Order::PAYMENT_PARTIALLY,
            Order::PAYMENT_PARTIALLY_REFUNDED,
            Order::PAYMENT_UNPAID,
            Order::PAYMENT_PAID,
        ] ) ) {
            throw new NotAllowedException( __( 'Unable to proceed a refund on an unpaid order.' ) );
        }

        /**
         * proceeding a refund should reduce the quantity
         * available on the order for a specific product.
         */
        $orderProduct->status = 'returned';
        $orderProduct->quantity -= floatval( $details[ 'quantity' ] );

        $this->computeOrderProduct( $orderProduct );

        $orderProduct->save();

        /**
         * Let's store a reference of
         * the refunded product
         */
        $productRefund = new OrderProductRefund;
        $productRefund->condition = $details[ 'condition' ];
        $productRefund->description = $details[ 'description' ];
        $productRefund->unit_price = $details[ 'unit_price' ];

        $productRefund->unit_id = $orderProduct->unit_id;
        $productRefund->total_price = $this->currencyService
            ->define( $productRefund->unit_price )->multipliedBy( $details[ 'quantity' ] )
            ->toFloat();

        $productRefund->quantity = $details[ 'quantity' ];
        $productRefund->author = Auth::id();
        $productRefund->order_id = $order->id;
        $productRefund->order_refund_id = $orderRefund->id;
        $productRefund->order_product_id = $orderProduct->id;
        $productRefund->product_id = $orderProduct->product_id;

        $productRefund->tax_value = $this->computeTaxFromOrderTaxes(
            $order,
            Currency::define( $details[ 'unit_price' ] )->multipliedBy( $details[ 'quantity' ] )->toFloat(),
            ns()->option->get( 'ns_pos_tax_type' )
        );

        $productRefund->save();

        event( new OrderAfterProductRefundedEvent( $order, $orderProduct, $productRefund ) );

        /**
         * We should adjust the stock only if a valid product
         * is being refunded.
         */
        if ( ! empty( $orderProduct->product_id ) ) {
            /**
             * we do proceed by doing an initial return
             */
            $this->productService->stockAdjustment( ProductHistory::ACTION_RETURNED, [
                'total_price' => $productRefund->total_price,
                'quantity' => $productRefund->quantity,
                'unit_price' => $productRefund->unit_price,
                'product_id' => $productRefund->product_id,
                'orderProduct' => $orderProduct,
                'unit_id' => $productRefund->unit_id,
                'order_id' => $order->id,
            ] );

            /**
             * If the returned stock is damaged
             * then we can pull this out from the stock
             */
            if ( $details[ 'condition' ] === OrderProductRefund::CONDITION_DAMAGED ) {
                $this->productService->stockAdjustment( ProductHistory::ACTION_DEFECTIVE, [
                    'total_price' => $productRefund->total_price,
                    'quantity' => $productRefund->quantity,
                    'unit_price' => $productRefund->unit_price,
                    'product_id' => $productRefund->product_id,
                    'orderProduct' => $orderProduct,
                    'unit_id' => $productRefund->unit_id,
                    'order_id' => $order->id,
                ] );
            }
        }

        return [
            'status' => 'success',
            'message' => sprintf(
                __( 'The product %s has been successfully refunded.' ),
                $orderProduct->name
            ),
            'data' => compact( 'productRefund', 'orderProduct' ),
        ];
    }

    /**
     * this method computes total for the current provided
     * order product
     *
     * @return void
     */
    public function computeOrderProduct( OrderProduct $orderProduct )
    {
        $orderProduct = $this->taxService->computeOrderProductTaxes( $orderProduct );

        OrderProductAfterComputedEvent::dispatch(
            $orderProduct,
        );
    }

    /**
     * compute a discount value using
     * provided values
     *
     * @param  float $rate
     * @param  float $value
     * @return float
     */
    public function computeDiscountValues( $rate, $value )
    {
        if ( $rate > 0 ) {
            return ns()->currency->define(
                ns()->currency->define( $value )->multiplyBy( $rate )->toFloat()
            )->divideBy( 100 )->toFloat();
        }

        return 0;
    }

    /**
     * Return a single order product
     *
     * @param int product id
     * @return OrderProduct
     */
    public function getOrderProduct( $product_id )
    {
        $product = OrderProduct::find( $product_id );

        if ( ! $product instanceof OrderProduct ) {
            throw new NotFoundException( __( 'Unable to find the order product using the provided id.' ) );
        }

        return $product;
    }

    /**
     * Get order products
     *
     * @param mixed identifier
     * @param string pivot
     * @return Collection
     */
    public function getOrderProducts( $identifier, $pivot = 'id' )
    {
        return $this->getOrder( $identifier, $pivot )
            ->products()
            ->validProducts()
            ->with( 'unit' )
            ->get();
    }

    /**
     * return a specific
     * order using a provided identifier and pivot
     *
     * @param mixed identifier
     * @param string pivot
     * @return Order
     */
    public function getOrder( $identifier, $as = 'id' )
    {
        if ( in_array( $as, ['id', 'code'] ) ) {
            $order = Order::where( $as, $identifier )
                ->with( 'payments' )
                ->with( 'shipping_address' )
                ->with( 'billing_address' )
                ->with( 'taxes' )
                ->with( 'instalments' )
                ->with( 'coupons' )
                ->with( 'products.unit' )
                ->with( 'products.product.unit_quantities' )
                ->with( 'customer.billing', 'customer.shipping' )
                ->first();

            if ( ! $order instanceof Order ) {
                throw new NotFoundException( sprintf(
                    __( 'Unable to find the requested order using "%s" as pivot and "%s" as identifier' ),
                    $as,
                    $identifier
                ) );
            }

            $order->products;

            /**
             * @deprecated
             */
            Hook::action( 'ns-load-order', $order );

            return $order;
        }

        throw new NotAllowedException( __( 'Unable to fetch the order as the provided pivot argument is not supported.' ) );
    }

    /**
     * Get all the order that has been
     * already created
     *
     * @param void
     * @return array of orders
     */
    public function getOrders( $filter = 'mixed' )
    {
        if ( in_array( $filter, ['paid', 'unpaid', 'refunded'] ) ) {
            return Order::where( 'payment_status', $filter )
                ->get();
        }

        return Order::get();
    }

    /**
     * Adding a product to an order
     *
     * @param Order order
     * @param array product
     * @return array response
     */
    public function addProducts( Order $order, $products )
    {
        $products = $this->__checkProductStock( collect( $products ), $order );

        /**
         * let's save the products
         * to the order now as the stock
         * seems to be okay
         *
         * @param array $orderProducts
         * @param Order $order
         * @param float $taxes
         * @param float $subTotal
         */
        extract( $this->__saveOrderProducts( $order, $products ) );

        /**
         * Now we should refresh the order
         * to have the total computed
         */
        $this->refreshOrder( $order );

        return [
            'status' => 'success',
            'data' => compact( 'orderProducts', 'order' ),
            'message' => sprintf(
                __( 'The product has been added to the order "%s"' ),
                $order->code
            ),
        ];
    }

    /**
     * refresh an order by computing
     * all the product total, taxes and
     * shipping all together.
     *
     * @param Order
     * @return array repsonse
     *
     * @todo test required
     */
    public function refreshOrder( Order $order )
    {
        $prevOrder = clone $order;

        $products = $this->getOrderProducts( $order->id );

        $productTotal = $products
            ->map( function ( $product ) {
                return floatval( $product->total_price );
            } )->sum();

        $productsQuantity = $products->map( function ( $product ) {
            return floatval( $product->quantity );
        } )->sum();

        $productPriceWithoutTax = $products
            ->map( function ( $product ) {
                return floatval( $product->total_price_without_tax );
            } )->sum();

        $productPriceWithTax = $products
            ->map( function ( $product ) {
                return floatval( $product->total_price_with_tax );
            } )->sum();

        $this->computeOrderTaxes( $order );

        $orderShipping = $order->shipping;
        $totalPayments = $order->payments->map( fn( $payment ) => $payment->value )->sum();
        $order->tendered = $totalPayments;

        /**
         * let's refresh all the order values
         */
        $order->subtotal = Currency::raw( $productTotal );
        $order->total_without_tax = $productPriceWithoutTax;
        $order->total_with_tax = $productPriceWithTax;
        $order->discount = $this->computeOrderDiscount( $order );
        $order->total = Currency::fresh( $order->subtotal )
            ->additionateBy( $orderShipping )
            ->additionateBy(
                ( $order->tax_type === 'exclusive' ? $order->tax_value : 0 )
            )
            ->subtractBy(
                ns()->currency->fresh( $order->discount )
                    ->additionateBy( $order->total_coupons )
                    ->toFloat()
            )
            ->toFloat();

        $order->change = Currency::fresh( $order->tendered )->subtractBy( $order->total )->toFloat();

        $refunds = $order->refunds;

        $totalRefunds = $refunds->map( fn( $refund ) => $refund->total )->sum();

        /**
         * We believe if the product total is greater
         * than "0", then probably the order hasn't been paid yet.
         */
        if ( (float) $order->total == 0 && $totalRefunds > 0 ) {
            $order->payment_status = Order::PAYMENT_REFUNDED;
        } elseif ( $order->total > 0 && $totalRefunds > 0 ) {
            $order->payment_status = Order::PAYMENT_PARTIALLY_REFUNDED;
        } elseif ( $order->tendered >= $order->total && $order->payments->count() > 0 && $totalRefunds == 0 ) {
            $order->payment_status = Order::PAYMENT_PAID;
        } elseif ( (float) $order->tendered < (float) $order->total && (float) $order->tendered > 0 ) {
            $order->payment_status = Order::PAYMENT_PARTIALLY;
        } elseif ( $order->total == 0 && $order->tendered == 0 ) {
            $order->payment_status = Order::PAYMENT_UNPAID;
        }

        $order->save();

        event( new OrderAfterUpdatedEvent(
            newOrder: $order,
            prevOrder: $prevOrder
        ) );

        return [
            'status' => 'success',
            'message' => __( 'the order has been successfully computed.' ),
            'data' => compact( 'order' ),
        ];
    }

    /**
     * Delete a specific order
     * and make product adjustment
     *
     * @param Order order
     * @return array response
     */
    public function deleteOrder( Order $order )
    {
        $cachedOrder = (object) $order->load( [
            'user',
            'products',
            'payments',
            'customer',
            'taxes',
            'coupons',
            'instalments',
        ] )->toArray();

        OrderBeforeDeleteEvent::dispatch( $cachedOrder );

        /**
         * Because when an order is void,
         * the stock is already returned to the inventory.
         */
        if ( ! in_array( $order->payment_status, [ Order::PAYMENT_VOID ] ) ) {
            $order
                ->products()
                ->get()
                ->each( function ( OrderProduct $orderProduct ) use ( $order ) {
                    $orderProduct->load( 'product' );
                    $product = $orderProduct->product;
                    /**
                     * we do proceed by doing an initial return
                     * only if the product is not a quick product/service
                     * we'll also check if the linked product still exists.
                     */
                    if (
                        ( $orderProduct->product_id > 0 && $product instanceof Product ) &&
                        (
                            in_array(
                                $order->payment_status, [
                                    Order::PAYMENT_PAID,
                                    Order::PAYMENT_PARTIALLY,
                                    Order::PAYMENT_UNPAID,
                                    Order::PAYMENT_PARTIALLY_DUE,
                                    Order::PAYMENT_PARTIALLY_REFUNDED,
                                ] )
                        )
                    ) {
                        $this->productService->stockAdjustment( ProductHistory::ACTION_RETURNED, [
                            'total_price' => $orderProduct->total_price,
                            'product_id' => $orderProduct->product_id,
                            'unit_id' => $orderProduct->unit_id,
                            'orderProduct' => $orderProduct,
                            'quantity' => $orderProduct->quantity,
                            'unit_price' => $orderProduct->unit_price,
                        ] );
                    }

                    $orderProduct->delete();
                } );
        }

        OrderPayment::where( 'order_id', $order->id )->delete();

        $orderArray = $order->toArray();
        $order->delete();

        OrderAfterDeletedEvent::dispatch( (object) $orderArray );

        return [
            'status' => 'success',
            'message' => __( 'The order has been deleted.' ),
        ];
    }

    /**
     * Delete a product that is included
     * within a specific order and refresh the order
     *
     * @param Order order instance
     * @param int product id
     * @return array response
     */
    public function deleteOrderProduct( Order $order, $product_id )
    {
        $hasDeleted = false;

        $order->products->map( function ( $product ) use ( $product_id, &$hasDeleted, $order ) {
            if ( $product->id === intval( $product_id ) ) {
                OrderBeforeDeleteProductEvent::dispatch( $order, $product );

                $product->delete();
                $hasDeleted = true;
            }
        } );

        if ( $hasDeleted ) {
            $this->refreshOrder( $order );

            return [
                'status' => 'success',
                'message' => __( 'The product has been successfully deleted from the order.' ),
            ];
        }

        throw new NotFoundException( __( 'Unable to find the requested product on the provider order.' ) );
    }

    /**
     * get orders payments
     *
     * @param int order id
     * @return array of payments
     */
    public function getOrderPayments( $orderID )
    {
        $order = $this->getOrder( $orderID );

        return $order->payments;
    }

    /**
     * Will return the active payments type.
     *
     * @return array
     */
    public function getPaymentTypes()
    {
        $payments = PaymentType::active()->get()->map( function ( $payment, $index ) {
            $payment->selected = $index === 0;

            return $payment;
        } );

        return collect( $payments )->mapWithKeys( function ( $payment ) {
            return [ $payment[ 'identifier' ] => $payment[ 'label' ] ];
        } )->toArray();
    }

    /**
     * It only returns what is the type of
     * the orders
     *
     * @param string
     * @return string
     */
    public function getTypeLabel( $type )
    {
        $types = $this->getTypeLabels();

        return $types[ $type ] ?? sprintf( __( 'Unknown Type (%s)' ), $type );
    }

    /**
     * It only returns what is the type of
     * the orders
     *
     * @param string
     * @return string
     */
    public function getPaymentLabel( $type )
    {
        $payments = config( 'nexopos.orders.statuses' );

        return $payments[ $type ] ?? sprintf( __( 'Unknown Status (%s)' ), $type );
    }

    /**
     * Returns the order payment labels
     *
     * @return array $labels
     */
    public function getPaymentLabels()
    {
        return config( 'nexopos.orders.statuses' );
    }

    public function getRefundedOrderProductLabel( $label )
    {
        return config( 'nexopos.orders.products.refunds' )[ $label ] ?? __( 'Unknown Product Status' );
    }

    /**
     * It only returns what is the type of
     * the orders
     *
     * @param string
     * @return string
     */
    public function getShippingLabel( $type )
    {
        $shipping = $this->getDeliveryStatuses();

        return $shipping[ $type ] ?? sprintf( _( 'Unknown Status (%s)' ), $type );
    }

    /**
     * It only returns the order process status
     *
     * @param string
     * @return string
     */
    public function getProcessStatus( $type )
    {
        $process = $this->getProcessStatuses();

        return $process[ $type ] ?? sprintf( _( 'Unknown Status (%s)' ), $type );
    }

    /**
     * It only returns the order process status
     *
     * @param string
     * @return string
     */
    public function getTypeLabels()
    {
        $types = Hook::filter( 'ns-order-types-labels', collect( $this->getTypeOptions() )->mapWithKeys( function ( $option ) {
            return [
                $option[ 'identifier' ] => $option[ 'label' ],
            ];
        } )->toArray() );

        return $types;
    }

    public function getTypeOptions()
    {
        return Hook::filter( 'ns-orders-types', [
            'takeaway' => [
                'identifier' => 'takeaway',
                'label' => __( 'Take Away' ),
                'icon' => '/images/groceries.png',
                'selected' => false,
            ],
            'delivery' => [
                'identifier' => 'delivery',
                'label' => __( 'Delivery' ),
                'icon' => '/images/delivery.png',
                'selected' => false,
            ],
        ] );
    }

    /**
     * Returns the order statuses
     *
     * @return array $statuses
     */
    public function getProcessStatuses()
    {
        return [
            'pending' => __( 'Pending' ),
            'ongoing' => __( 'Ongoing' ),
            'ready' => __( 'Ready' ),
            'not-available' => __( 'Not Available' ),
        ];
    }

    /**
     * Will return the delivery status for a defined
     * status provided as a string
     *
     * @param  string $status
     * @return string $response
     */
    public function getDeliveryStatus( $status )
    {
        $process = $this->getDeliveryStatuses();

        return $process[ $status ] ?? sprintf( _( 'Unknown Delivery (%s)' ), $status );
    }

    /**
     * Returns the order statuses
     *
     * @return array $statuses
     */
    public function getDeliveryStatuses()
    {
        return [
            'pending' => __( 'Pending' ),
            'ongoing' => __( 'Ongoing' ),
            'delivered' => __( 'Delivered' ),
            'error' => __( 'error' ),
            'not-available' => __( 'Not Available' ),
        ];
    }

    /**
     * parse and render options template
     * based on the provided values
     *
     * @param array options
     * @return string
     */
    public function orderTemplateMapping( $option, Order $order )
    {
        $template = ns()->option->get( $option, '' );
        $availableTags = [
            'store_name' => ns()->option->get( 'ns_store_name' ),
            'store_email' => ns()->option->get( 'ns_store_email' ),
            'store_phone' => ns()->option->get( 'ns_store_phone' ),
            'cashier_name' => $order->user->username,
            'cashier_id' => $order->author,
            'order_code' => $order->code,
            'order_type' => $this->getTypeLabel( $order->type ),
            'order_date' => ns()->date->getFormatted( $order->created_at ),
            'customer_first_name' => $order->customer->first_name,
            'customer_last_name' => $order->customer->last_name,
            'customer_email' => $order->customer->email,
            'shipping_' . 'first_name' => $order->shipping_address->first_name,
            'shipping_' . 'last_name' => $order->shipping_address->last_name,
            'shipping_' . 'phone' => $order->shipping_address->phone,
            'shipping_' . 'address_1' => $order->shipping_address->address_1,
            'shipping_' . 'address_2' => $order->shipping_address->address_2,
            'shipping_' . 'country' => $order->shipping_address->country,
            'shipping_' . 'city' => $order->shipping_address->city,
            'shipping_' . 'pobox' => $order->shipping_address->pobox,
            'shipping_' . 'company' => $order->shipping_address->company,
            'shipping_' . 'email' => $order->shipping_address->email,
            'billing_' . 'first_name' => $order->billing_address->first_name,
            'billing_' . 'last_name' => $order->billing_address->last_name,
            'billing_' . 'phone' => $order->billing_address->phone,
            'billing_' . 'address_1' => $order->billing_address->address_1,
            'billing_' . 'address_2' => $order->billing_address->address_2,
            'billing_' . 'country' => $order->billing_address->country,
            'billing_' . 'city' => $order->billing_address->city,
            'billing_' . 'pobox' => $order->billing_address->pobox,
            'billing_' . 'company' => $order->billing_address->company,
            'billing_' . 'email' => $order->billing_address->email,
        ];

        $availableTags = Hook::filter( 'ns-orders-template-mapping', $availableTags, $order );

        foreach ( $availableTags as $tag => $value ) {
            $template = ( str_replace( '{' . $tag . '}', $value ?: '', $template ) );
        }

        return $template;
    }

    /**
     * notify administrator when order
     * turned due (for layaway)
     *
     * @return array
     */
    public function notifyExpiredLaidAway()
    {
        $orders = Order::paymentExpired()->get();

        if ( ! $orders->isEmpty() ) {
            /**
             * The status changes according to the fact
             * if some orders has received a payment.
             */
            $orders->each( function ( $order ) {
                if ( $order->paid > 0 ) {
                    $order->payment_status = Order::PAYMENT_PARTIALLY_DUE;
                } else {
                    $order->payment_status = Order::PAYMENT_DUE;
                }

                $order->save();
            } );

            $notificationID = 'ns.due-orders-notifications';

            /**
             * let's clear previously emitted notification
             * with the specified identifier
             */
            Notification::identifiedBy( $notificationID )->delete();

            /**
             * @var NotificationService
             */
            $notificationService = app()->make( NotificationService::class );

            $notificationService->create( [
                'title' => __( 'Unpaid Orders Turned Due' ),
                'identifier' => $notificationID,
                'url' => ns()->route( 'ns.dashboard.orders' ),
                'description' => sprintf( __( '%s order(s) either unpaid or partially paid has turned due. This occurs if none has been completed before the expected payment date.' ), $orders->count() ),
            ] )->dispatchForGroup( [
                Role::namespace( 'admin' ),
                Role::namespace( 'nexopos.store.administrator' ),
            ] );

            DueOrdersEvent::dispatch( $orders );

            return [
                'status' => 'success',
                'message' => __( 'The operation was successful.' ),
            ];
        }

        return [
            'status' => 'error',
            'message' => __( 'No orders to handle for the moment.' ),
        ];
    }

    /**
     * Void a specific order
     * by keeping a trace of what has happened.
     *
     * @param Order
     * @param  string $reason
     * @return array
     */
    public function void( Order $order, $reason )
    {
        $order->products()
            ->get()
            ->each( function ( OrderProduct $orderProduct ) {
                $orderProduct->load( 'product' );

                if ( $orderProduct->product instanceof Product ) {
                    /**
                     * we do proceed by doing an initial return
                     */
                    $this->productService->stockAdjustment( ProductHistory::ACTION_VOID_RETURN, [
                        'total_price' => $orderProduct->total_price,
                        'product_id' => $orderProduct->product_id,
                        'unit_id' => $orderProduct->unit_id,
                        'orderProduct' => $orderProduct,
                        'quantity' => $orderProduct->quantity,
                        'unit_price' => $orderProduct->unit_price,
                    ] );
                }
            } );

        $order->payment_status = Order::PAYMENT_VOID;
        $order->voidance_reason = $reason;
        $order->save();

        event( new OrderVoidedEvent( $order ) );

        return [
            'status' => 'success',
            'message' => __( 'The order has been correctly voided.' ),
        ];
    }

    /**
     * get orders sold during a specific perdiod
     *
     * @param  string     $startDate range starts
     * @param  string     $endDate   range ends
     * @return Collection
     */
    public function getPaidSales( $startDate, $endDate )
    {
        return Order::paid()
            ->where( 'created_at', '>=', Carbon::parse( $startDate )->toDateTimeString() )
            ->where( 'created_at', '<=', Carbon::parse( $endDate )->toDateTimeString() )
            ->get();
    }

    /**
     * get sold stock during a specific period
     *
     * @param  string     $startDate range starts
     * @param  string     $endDate   range ends
     * @return Collection
     */
    public function getSoldStock( $startDate, $endDate, $categories = [], $units = [] )
    {
        $selectedColumns = [ 'product_id', 'name', 'unit_name', 'unit_price', 'quantity', 'total_purchase_price', 'tax_value', 'total_price' ];
        $groupColumns = [ 'product_id', 'unit_id', 'name', 'unit_name' ];
        $selectedColumns = [
            ...$groupColumns,
            DB::raw( 'SUM(quantity) as quantity' ),
            DB::raw( 'SUM(tax_value) as tax_value' ),
            DB::raw( 'SUM(total_purchase_price) as total_purchase_price' ),
            DB::raw( 'SUM(total_price) as total_price' ),
        ];
        $rangeStarts = Carbon::parse( $startDate )->toDateTimeString();
        $rangeEnds = Carbon::parse( $endDate )->toDateTimeString();

        $products = OrderProduct::whereHas( 'order', function ( Builder $query ) {
            $query->where( 'payment_status', Order::PAYMENT_PAID );
        } )
            ->select( $selectedColumns )
            ->groupByRaw( implode( ', ', $groupColumns ) )
            ->where( 'created_at', '>=', $rangeStarts )
            ->where( 'created_at', '<=', $rangeEnds );

        if ( ! empty( $categories ) ) {
            $products->whereIn( 'product_category_id', $categories );
        }

        if ( ! empty( $units ) ) {
            $products->whereIn( 'unit_id', $units );
        }

        return $products->get();
    }

    public function trackOrderCoupons( Order $order )
    {
        $order->coupons()->where( 'counted', false )->each( function ( OrderCoupon $orderCoupon ) {
            $customerCoupon = CustomerCoupon::find( $orderCoupon->customer_coupon_id );

            if ( ! $customerCoupon instanceof CustomerCoupon ) {
                throw new NotFoundException( sprintf(
                    __( 'Unable to find a reference of the provided coupon : %s' ),
                    $orderCoupon->name
                ) );
            }

            $this->customerService->increaseCouponUsage( $customerCoupon );

            $orderCoupon->counted = true;
            $orderCoupon->save();
        } );
    }

    /**
     * Will resolve instalments attached to an order
     *
     * @return void
     */
    public function resolveInstalments( Order $order )
    {
        if ( in_array( $order->payment_status, [ Order::PAYMENT_PAID, Order::PAYMENT_PARTIALLY ] ) ) {
            $orderInstalments = $order->instalments()
                ->where( 'date', '>=', ns()->date->copy()->startOfDay()->toDateTimeString() )
                ->where( 'date', '<=', ns()->date->copy()->endOfDay()->toDateTimeString() )
                ->where( 'paid', false )
                ->get();

            $paidInstalments = $order->instalments()->where( 'paid', true )->sum( 'amount' );
            // $otherInstalments = $order->instalments()->whereNotIn( 'id', $orderInstalments->only( 'id' )->toArray() )->sum( 'amount' );
            // $dueInstalments = Currency::raw( $orderInstalments->sum( 'amount' ) );

            if ( $orderInstalments->count() > 0 ) {
                $payableDifference = Currency::define( $order->tendered )
                    ->subtractBy( $paidInstalments )
                    ->toFloat();

                $orderInstalments
                    ->each( function ( $instalment ) use ( &$payableDifference ) {
                        if ( $payableDifference - $instalment->amount >= 0 ) {
                            $instalment->paid = true;
                            $instalment->save();
                            $payableDifference -= $instalment->amount;
                        }
                    } );
            }
        }
    }

    /**
     * Will update an existing instalment
     *
     * @param  OrderInstalment $orderInstalement
     * @param  array           $fields
     * @return array
     */
    public function updateInstalment( Order $order, OrderInstalment $instalment, $fields )
    {
        if ( $instalment->paid ) {
            throw new NotAllowedException( __( 'Unable to edit an already paid instalment.' ) );
        }

        foreach ( $fields as $field => $value ) {
            if ( in_array( $field, [ 'date', 'amount' ] ) ) {
                $instalment->$field = $value;
            }
        }

        $instalment->save();

        return [
            'status' => 'success',
            'message' => __( 'The instalment has been saved.' ),
            'data' => compact( 'instalment' ),
        ];
    }

    /**
     * Will make an instalment as paid
     *
     * @return array
     */
    public function markInstalmentAsPaid( Order $order, OrderInstalment $instalment, $paymentType = OrderPayment::PAYMENT_CASH )
    {
        if ( $instalment->paid ) {
            throw new NotAllowedException( __( 'Unable to edit an already paid instalment.' ) );
        }

        $payment = [
            'order_id' => $order->id,
            'identifier' => $paymentType,
            'author' => Auth::id(),
            'value' => $instalment->amount,
        ];

        $result = $this->makeOrderSinglePayment( $payment, $order );
        $payment = $result[ 'data' ][ 'payment' ];

        $instalment->paid = true;
        $instalment->payment_id = $payment->id;
        $instalment->save();

        OrderAfterInstalmentPaidEvent::dispatch( $instalment, $order );

        return [
            'status' => 'success',
            'message' => __( 'The instalment has been saved.' ),
            'data' => compact( 'instalment', 'payment' ),
        ];
    }

    /**
     * Will delete an instalment.
     *
     * @param  OrderInstlament $instalment
     * @return array
     */
    public function deleteInstalment( Order $order, OrderInstalment $instalment )
    {
        $instalment->delete();

        $this->refreshInstalmentCount( $order );

        return [
            'status' => 'success',
            'message' => __( 'The instalment has been deleted.' ),
        ];
    }

    public function refreshInstalmentCount( Order $order )
    {
        $order->total_instalments = $order->instalments()->count();
        $order->save();
    }

    /**
     * Creates an instalments
     *
     * @param  array $fields
     * @return array
     */
    public function createInstalment( Order $order, $fields )
    {
        $totalInstalment = $order->instalments->map( fn( $instalment ) => $instalment->amount )->sum();

        if ( Currency::raw( $fields[ 'amount' ] ) <= 0 ) {
            throw new NotAllowedException( __( 'The defined amount is not valid.' ) );
        }

        if ( Currency::raw( $totalInstalment ) >= $order->total ) {
            throw new NotAllowedException( __( 'No further instalments is allowed for this order. The total instalment already covers the order total.' ) );
        }

        if ( $fields[ 'amount' ] ) {
            $orderInstalment = new OrderInstalment;
            $orderInstalment->order_id = $order->id;
            $orderInstalment->amount = $fields[ 'amount' ];
            $orderInstalment->date = $fields[ 'date' ];
            $orderInstalment->save();

            $this->refreshInstalmentCount( $order );
        }

        return [
            'status' => 'success',
            'message' => __( 'The instalment has been created.' ),
            'data' => [
                'instalment' => $orderInstalment,
            ],
        ];
    }

    /**
     * Changes the order processing status
     *
     * @param  string $status
     * @return array
     */
    public function changeProcessingStatus( Order $order, $status )
    {
        if ( ! in_array( $status, [
            Order::PROCESSING_PENDING,
            Order::PROCESSING_ONGOING,
            Order::PROCESSING_READY,
            Order::PROCESSING_FAILED,
        ] ) ) {
            throw new NotAllowedException( __( 'The provided status is not supported.' ) );
        }

        $order->process_status = $status;
        $order->save();

        OrderAfterUpdatedProcessStatus::dispatch( $order );

        return [
            'status' => 'success',
            'message' => __( 'The order has been successfully updated.' ),
        ];
    }

    /**
     * Changes the order processing status
     *
     * @param  string $status
     * @return array
     */
    public function changeDeliveryStatus( Order $order, $status )
    {
        if ( ! in_array( $status, [
            Order::DELIVERY_COMPLETED,
            Order::DELIVERY_DELIVERED,
            Order::DELIVERY_FAILED,
            Order::DELIVERY_ONGOING,
            Order::DELIVERY_PENDING,
        ] ) ) {
            throw new NotAllowedException( __( 'The provided status is not supported.' ) );
        }

        $order->delivery_status = $status;
        $order->save();

        OrderAfterUpdatedDeliveryStatus::dispatch( $order );

        return [
            'status' => 'success',
            'message' => __( 'The order has been successfully updated.' ),
        ];
    }

    public function getPaymentTypesReport( $startRange, $endRange )
    {
        $paymentTypes = PaymentType::active()->get();
        $paymentsIdentifier = $paymentTypes->map( fn( $paymentType ) => $paymentType->identifier )->toArray();

        $payments = OrderPayment::where( 'created_at', '>=', $startRange )
            ->where( 'created_at', '<=', $endRange )
            ->whereIn( 'identifier', $paymentsIdentifier )
            ->whereRelation( 'order', 'payment_status', Order::PAYMENT_PAID )
            ->get();

        $total = $payments->map( fn( $payment ) => $payment->value )->sum();

        return [
            'summary' => $paymentTypes->map( function ( $paymentType ) use ( $payments ) {
                $total = $payments
                    ->filter( fn( $payment ) => $payment->identifier === $paymentType->identifier )
                    ->map( fn( $payment ) => $payment->value )
                    ->sum();

                return [
                    'label' => $paymentType->label,
                    'total' => ns()->currency->define( $total )->toFloat(),
                ];
            } ),
            'total' => ns()->currency->define( $total )->toFloat(),
            'entries' => $payments,
        ];
    }

    /**
     * Will return the product that
     * that has been refunded
     *
     * @return array
     */
    public function getOrderRefundedProducts( Order $order )
    {
        return $order->refundedProducts;
    }

    /**
     * Will return the order refund along
     * with the product refunded
     *
     * @return OrderRefund
     */
    public function getOrderRefunds( Order $order )
    {
        $order->load( 'refunds.refunded_products.product', 'refunds.refunded_products.unit', 'refunds.author' );

        return $order;
    }
}
