<?php

namespace App\Services;

use App\Classes\Currency;
use App\Classes\Hook;
use App\Events\DueOrdersEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\Exceptions\NotFoundException;
use App\Exceptions\NotAllowedException;
use App\Events\OrderBeforeDeleteProductEvent;
use App\Events\OrderAfterProductRefundedEvent;
use App\Events\OrderAfterCreatedEvent;
use App\Events\OrderAfterDeletedEvent;
use App\Events\OrderAfterPaymentCreatedEvent;
use App\Events\OrderAfterProductStockCheckedEvent;
use App\Events\OrderAfterRefundedEvent;
use App\Events\OrderAfterUpdatedEvent;
use App\Events\OrderBeforeDeleteEvent;
use App\Events\OrderBeforePaymentCreatedEvent;
use App\Events\OrderProductAfterSavedEvent;
use App\Events\OrderRefundPaymentAfterCreatedEvent;
use App\Events\OrderVoidedEvent;
use App\Models\Order;
use App\Models\Product;
use App\Models\OrderAddress;
use App\Models\OrderPayment;
use App\Models\OrderProduct;
use App\Models\Customer;
use App\Models\CustomerAccountHistory;
use App\Models\CustomerCoupon;
use App\Models\Notification;
use App\Models\OrderCoupon;
use App\Models\OrderInstalment;
use App\Models\OrderProductRefund;
use App\Models\OrderRefund;
use App\Models\OrderStorage;
use App\Models\OrderTax;
use App\Models\ProcurementProduct;
use App\Models\ProductHistory;
use App\Models\ProductUnitQuantity;
use App\Models\Role;
use App\Models\Unit;
use App\Services\Options;
use App\Services\DateService;
use App\Services\ProductService;
use App\Services\CurrencyService;
use App\Services\CustomerService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class OrdersService
{
    /** @var CustomerService */
    protected $customerService;

    /** @var ProductService */
    protected $productService;

    /** @var UnitService */
    protected $unitService;

    /** @var DateService */
    protected $dateService;

    /** @var CurrencyService */
    protected $currencyService;

    /** @var Options */
    protected $optionsService;

    /** @var TaxService */
    protected $taxService;

    public function __construct(
        CustomerService $customerService,
        ProductService $productService,
        UnitService $unitService,
        DateService $dateService,
        CurrencyService $currencyService,
        Options $optionsService,
        TaxService $taxService
    ) {
        $this->customerService  =   $customerService;
        $this->productService   =   $productService;
        $this->dateService      =   $dateService;
        $this->unitService      =   $unitService;
        $this->currencyService  =   $currencyService;
        $this->optionsService   =   $optionsService;
        $this->taxService       =   $taxService;
    }

    public function create( $fields, Order $order = null )
    {
        $customer               =   $this->__customerIsDefined($fields);
        $fields[ 'products' ]   =   $this->__buildOrderProducts( $fields['products'] );

        /**
         * determine the value of the product 
         * on the cart and compare it along with the payment made. This will
         * help to prevent partial or quote orders
         * @param float $total
         * @param float $totalPayments
         * @param array $payments
         * @param string $paymentStatus
         */
        extract( $this->__checkOrderPayments( $fields, $order, $customer ) );

        /**
         * We'll now check the attached coupon
         * and determin wether they can be processed.
         */
        $this->__checkAttachedCoupons( $fields[ 'coupons' ] ?? [] );
        
        /**
         * As no payment might be provided
         * we make sure to build the products only in case the
         * order is just saved as hold, otherwise a check is made on the available stock
         */
        if ( in_array( $paymentStatus, [ 'paid', 'partially_paid', 'unpaid' ] ) ) {
            $fields[ 'products' ]      =   $this->__checkProductStock( $fields['products'] );
        }

        /**
         * check discount validity and throw an
         * error is something is not set correctly.
         */
        $this->__checkDiscountVality( $fields );

        /**
         * check delivery informations before
         * proceeding
         */
        $this->__checkAddressesInformations( $fields );

        /**
         * Check if instalments are provided and if they are all
         * valid regarding the total order price
         */
        $this->__checkProvidedInstalments( $fields );

        /**
         * ------------------------------------------
         *                  WARNING
         * ------------------------------------------
         * all what follow will proceed database 
         * modification. All verifications on current order
         * should be made prior this section
         */
        $order      =   $this->__initOrder( $fields, $paymentStatus, $order );

        /**
         * save order instalments
         */
        $this->__saveOrderInstalments( $order, $fields[ 'instalments' ] ?? [] );

        /**
         * register taxes for the order
         */
        $this->__registerTaxes( $order, $fields[ 'taxes' ] ?? [] );

        /**
         * if we're editing an order. We need to loop the products in order
         * to recover all the products that has been deleted from the POS and therefore
         * aren't tracked no more.
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
            Order::PAYMENT_UNPAID 
        ] ) ) {
            $this->__saveOrderPayments( $order, $payments, $customer );
        }

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
         * compute order total
         */
        $this->__computeOrderTotal( compact( 'order', 'subTotal', 'taxes', 'paymentStatus', 'totalPayments') );

        $order->save();
        $order->load( 'payments' );
        $order->load( 'products' );

        /**
         * let's notify when an
         * new order has been placed
         */
        event( new OrderAfterCreatedEvent( $order, $fields ) );

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The order has been placed.' ),
            'data'      =>  compact( 'order' )
        ];
    }

    public function __saveOrderInstalments( Order $order, $instalments = [] )
    {
        if ( ! empty( $instalments ) ) {
            /**
             * delete previous instalments
             */
            $order->instalments->each( fn( $instalment ) => $instalment->delete() );

            foreach( $instalments as $instalment ) {
                $newInstalment              =   new OrderInstalment;
                $newInstalment->amount      =   $instalment[ 'amount' ];
                $newInstalment->order_id    =   $order->id;
                $newInstalment->date        =   Carbon::parse( $instalment[ 'date' ] )->toDateTimeString();
                $newInstalment->save();
            }
        }
    }

    /**
     * check if the provided instalments are
     * valid and verify it allong with the order
     * total.
     * @param array $fields
     * @return void
     */
    public function __checkProvidedInstalments( $fields )
    {
        if ( isset( $fields[ 'instalments' ] ) && ! empty( $fields[ 'instalments' ] ) ) {
            $instalments    =   collect( $fields[ 'instalments' ] );
            $total          =   $instalments->sum( 'amount' );
            $customer       =   Customer::find( $fields[ 'customer_id' ] );

            if ( $customer->group->minimal_credit_payment > 0 ) {
                $minimal        =   Currency::define( $fields[ 'total' ] )
                    ->multipliedBy( $customer->group->minimal_credit_payment )
                    ->dividedBy( 100 )
                    ->getRaw();

                /**
                 * if the minimal provided
                 * amount thoses match the required amount.
                 */
                if ( $minimal > Currency::raw( $fields[ 'tendered' ] ) ) {
                    throw new NotAllowedException( 
                        sprintf(
                            __( 'The minimal payment of %s has\'nt been provided.' ),
                            ( string ) Currency::define( $minimal )
                        )
                    );
                }
            }
            
            if ( $total < ( float ) $fields[ 'total' ] ) {
                throw new NotAllowedException( __( 'Unable to save an order with instalments amounts that doesn\'t match the order total.' ) );
            }

            $instalments->each( function( $instalment ) {
                if ( ns()->date->copy()->startOfDay()->greaterThan( Carbon::parse( $instalment[ 'date' ] ) ) ) {
                    throw new NotAllowedException( 
                        sprintf( __( 'An instalment has an invalid date of %s, which shouldn\'t be prior the current day.' ), $instalment[ 'date' ] ) 
                    );
                }
            });

            $paidToday      =   $instalments->map( function( $instalment ) {
                if ( ns()->date->copy()->isSameDay( Carbon::parse( $instalment[ 'date' ] ) ) ) {
                    return ( float ) $instalment[ 'amount' ];
                }
                return 0;
            });

            if ( Currency::raw( $paidToday->sum() ) !== Currency::raw( $fields[ 'tendered' ] ) ) {
                throw new NotAllowedException( __( 'The total amount to be paid today is different from the tendered amount.' ) );
            }
        }
    }

    /**
     * Checks wether the attached coupons are valid
     * @param array $coupons
     * @return void
     */
    public function __checkAttachedCoupons( $coupons )
    {
        collect( $coupons )->each( function( $coupon ) {
            $customerCoupon     =   CustomerCoupon::find( $coupon[ 'customer_coupon_id' ] );

            if ( ! $customerCoupon instanceof CustomerCoupon ) {
                throw new NotFoundException( sprintf( __( 'Unable to find a reference to the attached coupon : %s' ), $coupon[ 'name' ] ?? __( 'N/A' ) ) );
            }

            if ( ! $customerCoupon->active ) {
                throw new NotFoundException( sprintf( __( 'The provided coupon "%s", can no longer be used' ), $customerCoupon->name ) );
            }
        });
    }

    /**
     * Computes the total of the provided coupons
     * @param array $fields
     * @param float $subtotal
     * @return float
     */
    private function __computeOrderCoupons( $fields, $subtotal )
    {
        if ( isset( $fields[ 'coupons' ] ) ) {
            return collect( $fields[ 'coupons' ] )->map( fn( $coupon ) => $coupon[ 'value' ] )->sum();
        }

        return 0;
    }

    /**
     * Save the coupons by attaching them to the processed order
     * @param Order $order
     * @param array $coupons
     * @return void
     */
    public function __saveOrderCoupons( Order $order, $coupons )
    {
        $savedCoupons       =   [];

        $order->total_coupons   =   0;

        foreach( $coupons as $coupon ) {
            $existingCoupon     =   OrderCoupon::find( $coupon[ 'id' ] ?? null );

            if ( ! $existingCoupon instanceof OrderCoupon ) {
                $existingCoupon                         =   new OrderCoupon;
                $existingCoupon->order_id               =   $order->id;
                $existingCoupon->customer_coupon_id     =   $coupon[ 'customer_coupon_id' ];
                $existingCoupon->minimum_cart_value     =   $coupon[ 'minimum_cart_value' ] ?: 0;
                $existingCoupon->maximum_cart_value     =   $coupon[ 'maximum_cart_value' ] ?: 0;
                $existingCoupon->name                   =   $coupon[ 'name' ] ?: 0;
                $existingCoupon->type                   =   $coupon[ 'type' ] ?: 0;
                $existingCoupon->limit_usage            =   $coupon[ 'limit_usage' ] ?: 0;
                $existingCoupon->code                   =   $coupon[ 'code' ];
                $existingCoupon->author                 =   Auth::id();
                $existingCoupon->discount_value         =   $coupon[ 'discount_value' ] ?: 0;
            }

            $existingCoupon->value                      =   $coupon[ 'value' ] ?: ( 
                $coupon[ 'discount_type' ] === 'percentage_discount' ? 
                    $this->computeDiscountValues( $coupon[ 'discount_value' ], $order->subtotal ) : 
                    $coupon[ 'discount_value' ]
            );

            /**
             * that should compute
             * the coupons value automatically
             */
            $order->total_coupons   +=  $existingCoupon->value;

            $existingCoupon->save();

            $savedCoupons[]     =   $existingCoupon->id;
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
     * Assign taxes to the processed order
     * @param Order $order
     * @param array $taxes
     * @return void
     */
    public function __registerTaxes( Order $order, $taxes )
    {
        /**
         * if previous taxes had been registered,
         * we need to clear them
         */
        OrderTax::where( 'order_id', $order->id )->delete();

        if ( count( $taxes ) > 0 ) {
            foreach( $taxes as $tax ) {
                $orderTax   =   new OrderTax;
                $orderTax->tax_name     =   $tax[ 'tax_name' ];
                $orderTax->tax_value    =   $tax[ 'tax_value' ];
                $orderTax->rate         =   $tax[ 'rate' ];
                $orderTax->tax_id       =   $tax[ 'tax_id' ];
                $orderTax->order_id     =   $order->id;
                $orderTax->save();
            }
        }
    }

    /**
     * will delete the products belonging to an order
     * that aren't tracked.
     * @param Order $order
     * @param Array $products
     * @return void
     */
    public function __deleteUntrackedProducts( $order, $products ) 
    {
        if ( $order instanceof Order ) {
            $ids    =   collect( $products )
                ->filter( fn( $product ) => isset( $product[ 'id' ] ) )
                ->map( fn( $product ) => $product[ 'id' ] . '-' . $product[ 'unit_id' ] ?? false )
                ->filter( fn( $product ) => $product !== false )
                ->toArray();

            /**
             * While the order is being edited, we'll check if the new quantity of 
             * each product is different from the previous known quantity, to perform
             * adjustment accordingly. In that case we'll use adjustment-return & sale.
             */
            if ( $order->payment_status !== Order::PAYMENT_HOLD ) {
                $adjustments    =   $order->products->map( function( OrderProduct $product ) use ( $products ) {
                        $products    =   collect( $products )
                            ->mapWithKeys( fn( $product ) => [ $product[ 'id' ] => $product ] )
                            ->toArray();

                        if ( in_array( $product->id, array_keys( $products ) ) ) {
                            if ( $product->quantity < $products[ $product->id ][ 'quantity' ] ) {
                                return [
                                    'operation'     =>      'add',
                                    'unit_price'    =>      $products[ $product->id ][ 'unit_price' ],
                                    'total_price'   =>      $products[ $product->id ][ 'total_price' ],
                                    'quantity'      =>      $products[ $product->id ][ 'quantity' ] - $product->quantity,
                                    'orderProduct'  =>      $product
                                ];
                            } else if ( $product->quantity > $products[ $product->id ][ 'quantity' ] ) {
                                return [
                                    'operation'     =>      'remove',
                                    'unit_price'    =>      $products[ $product->id ][ 'unit_price' ],
                                    'total_price'   =>      $products[ $product->id ][ 'total_price' ],
                                    'quantity'      =>      $product->quantity - $products[ $product->id ][ 'quantity' ],
                                    'orderProduct'  =>      $product
                                ];
                            }
                        }

                        /**
                         * when to change has been made on
                         * the order product
                         */
                        return false;
                    })
                    ->filter( fn( $adjustment ) => $adjustment !== false )
                    ->each( function( $adjustment ) use ( $order ) {
                        if ( $adjustment[ 'operation' ]  ===  'remove' ) {
                            $adjustment[ 'orderProduct' ]->quantity         -=   $adjustment[ 'quantity' ];

                            $this->productService->stockAdjustment(
                                ProductHistory::ACTION_ADJUSTMENT_RETURN, [
                                    'unit_id'       =>  $adjustment[ 'orderProduct' ]->unit_id,
                                    'unit_price'    =>  $adjustment[ 'orderProduct' ]->unit_price,
                                    'product_id'    =>  $adjustment[ 'orderProduct' ]->product_id,
                                    'quantity'      =>  $adjustment[ 'quantity' ],
                                    'order_id'      =>  $order->id
                                ]
                            );
                        } else {
                            $adjustment[ 'orderProduct' ]->quantity         +=   $adjustment[ 'quantity' ];

                            $this->productService->stockAdjustment(
                                ProductHistory::ACTION_ADJUSTMENT_SALE, [
                                    'unit_id'       =>  $adjustment[ 'orderProduct' ]->unit_id,
                                    'unit_price'    =>  $adjustment[ 'orderProduct' ]->unit_price,
                                    'product_id'    =>  $adjustment[ 'orderProduct' ]->product_id,
                                    'quantity'      =>  $adjustment[ 'quantity' ],
                                    'order_id'      =>  $order->id
                                ]
                            );
                        }

                        /**
                         * for the product that was already tracked
                         * we'll just update the price and quantity
                         */
                        $adjustment[ 'orderProduct' ]->unit_price       =   $adjustment[ 'unit_price' ];
                        $adjustment[ 'orderProduct' ]->total_price      =   $adjustment[ 'total_price' ];
                        $adjustment[ 'orderProduct' ]->save();
                });
            }

            /**
             * Every product that is missing when the order is being
             * proceesed another time should be removed. If the order has
             * already affected the stock, we should make some adjustments.
             */            
            $order->products->each( function( $orderProduct ) use ( $ids, $order ) {
                
                /**
                 * if a product has the unit id changed
                 * the product he considered as new and the old is returned
                 * to the stock.
                 */
                $reference  =   $orderProduct->id . '-' . $orderProduct->unit_id;

                if ( ! in_array( $reference, $ids ) ) {
                    $orderProduct->delete();

                    /**
                     * If the order has changed the stock. The operation
                     * that update it should affect the stock as well.
                     */
                    if ( $order->payment_status !== Order::PAYMENT_HOLD ) {
                        $this->productService->stockAdjustment(
                            ProductHistory::ACTION_ADJUSTMENT_RETURN, [
                                'unit_id'       =>  $orderProduct->unit_id,
                                'unit_price'    =>  $orderProduct->unit_price,
                                'product_id'    =>  $orderProduct->product_id,
                                'quantity'      =>  $orderProduct->quantity,
                                'order_id'      =>  $order->id
                            ]
                        );
                    }
                }
            });
        }
    }

    /**
     * get the current shipping
     * feels
     * @param array fields
     */
    private function __getShippingFee($fields): float
    {
        return $this->currencyService->getRaw( $fields['shipping'] ?? 0 );
    }

    /**
     * Check wether a discount is valid or 
     * not
     * @param array fields
     * @return void|Exception
     */
    public function __checkDiscountVality( $fields )
    {
        if (!empty(@$fields['discount_type'])) {

            if ($fields['discount_type'] === 'percentage' && (floatval($fields['discount_percentage']) < 0) || (floatval($fields['discount_percentage']) > 100)) {
                throw new NotAllowedException([
                    'status'    =>  'failed',
                    'message'   =>  __('The percentage discount provided is not valid.')
                ]);
            } else if ($fields['discount_type'] === 'flat') {

                $productsTotal    =   $fields[ 'products' ]->map(function ($product) {
                    return $this->currencyService->define( $product['quantity'] )
                        ->multiplyBy( floatval($product['sale_price']) )
                        ->getRaw();
                })->sum();

                if ( $fields['discount'] > $productsTotal ) {
                    throw new NotAllowedException([
                        'status'    =>  'failed',
                        'message'   =>  __('A discount cannot exceed the sub total value of an order.')
                    ]);
                }
            }
        }
    }

    /**
     * Check defined address informations
     * and throw an error if a fields is not supported
     * @param array fields
     * @return void
     */
    private function __checkAddressesInformations($fields)
    {
        $allowedKeys    =   [
            'id',
            'name',
            'surname',
            'phone',
            'address_1',
            'address_2',
            'country',
            'city',
            'pobox',
            'company',
            'email'
        ];

        /**
         * this will erase the unsupported
         * attribute before saving the order.
         */
        if ( ! empty( $fields[ 'addresses' ] ) ) {
            foreach (['shipping', 'billing'] as $type) {
                $keys   =   array_keys($fields['addresses'][$type]);
                foreach ($keys as $key) {
                    if (!in_array($key, $allowedKeys)) {
                        unset( $fields[ 'addresses' ][ $type ][ $key ] );
                    }
                }
            }
        }
    }

    /**
     * Save address informations
     * for a specific order
     * @param Order
     * @param array of key=>value fields submitted
     */
    private function __saveAddressInformations($order, $fields)
    {
        foreach (['shipping', 'billing'] as $type) {

            /**
             * if the id attribute is already provided
             * we should attempt to find the related addresses
             * and use that as a reference otherwise create a new instance.
             * @todo add a verification to enforce address to be attached
             * to the processed order.
             */
            if ( isset( $fields[ 'addresses' ][ $type ][ 'id' ] ) ) {
                $orderShipping          =   OrderAddress::find( $fields[ 'addresses' ][ $type ][ 'id' ] );
            } else {
                $orderShipping          =   new OrderAddress;
            }

            $orderShipping->type    =   $type;

            if (!empty($fields['addresses'][$type])) {
                foreach ($fields['addresses'][$type] as $key => $value) {
                    $orderShipping->$key    =   $value;
                }
            }
            
            $orderShipping->author      =   Auth::id();
            $orderShipping->order_id    =   $order->id;
            $orderShipping->save();
        }
    }

    private function __saveOrderPayments($order, $payments, $customer )
    {
        /**
         * As we're about to record new payments,
         * we first need to delete previous payments that
         * might have been made. Probably we'll need to keep these
         * order and only update them.
         */
        foreach ($payments as $payment) {
            $this->__saveOrderSinglePayment( $payment, $order );
        }

        $order->tendered    =   $this->currencyService->getRaw( collect( $payments )->map( fn( $payment ) => floatval( $payment[ 'value' ] ) )->sum() );
    }

    /**
     * Perform a single payment to a provided order
     * and ensure to display relevant events
     * @param array $payment
     * @param Order $order
     * @return array
     */
    public function makeOrderSinglePayment( $payment, Order $order )
    {
        if( $order->instalments->count() > 0 ) {
            $paymentToday    =   $order->instalments()
                ->where( 'paid', false )
                ->where( 'date', '>=', ns()->date->copy()->startOfDay()->toDateTimeString() )
                ->where( 'date', '<=', ns()->date->copy()->endOfDay()->toDateTimeString() )
                ->get();
    
            if ( $paymentToday->count() === 0 ) {
                throw new NotFoundException( __( 'No payment is expected at the moment. If the customer want to pay early, consider adjusting instalment payments date.' ) );
            }

            if ( 
                ns()->currency->getRaw( $paymentToday->sum( 'amount' ) ) !== 
                ns()->currency->getRaw( $payment[ 'value' ] ) ) {
                throw new NotAllowedException( 
                    sprintf(
                        __( 'The provided payment doesn\'t match the expected payment : %s. If the customer want to pay a different amount, consider adjusting the instalment amount.' ),
                        ( string ) Currency::define( $paymentToday->sum( 'amount' ) )
                    )
                );
            }
        }        

        $this->__saveOrderSinglePayment( $payment, $order );
        
        /**
         * let's refresh the order to check wether the 
         * payment has made the order complete or not.
         */
        $order->refresh();
        
        $this->refreshOrder( $order );

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The payment has been saved.' ),
        ];
    }

    /**
     * Save an order payment (or update). deplete customer
     * account if "account payment" is used.
     * 
     * @param array $payment
     * @param Order $order
     * @return OrderPayment
     */
    private function __saveOrderSinglePayment( $payment, Order $order ): OrderPayment
    {
        event( new OrderBeforePaymentCreatedEvent( $payment, $order->customer ) );

        $orderPayment       =  isset( $payment[ 'id' ] ) ? OrderPayment::find( $payment[ 'id' ] ) : false;

        if ( ! $orderPayment instanceof OrderPayment ) {
            $orderPayment       =   new OrderPayment;
        }

        $orderPayment->order_id     =   $order->id;
        $orderPayment->identifier   =   $payment['identifier'];
        $orderPayment->value        =   $this->currencyService->getRaw( $payment['value'] );
        $orderPayment->author       =   Auth::id();
        $orderPayment->save();

        /**
         * When the customer is making some payment
         * we store it on his history.
         */
        if ( $payment[ 'identifier' ] === OrderPayment::PAYMENT_ACCOUNT ) {
            $this->customerService->saveTransaction( 
                $order->customer, 
                CustomerAccountHistory::OPERATION_PAYMENT, 
                $payment[ 'value' ] 
            );
        }

        event( new OrderAfterPaymentCreatedEvent( $orderPayment, $order ) );

        return $orderPayment;
    }

    /**
     * Checks the order payements and compare
     * it to the product values and determine
     * if the order can proceed
     * @param Collection $products
     * @param array field
     */
    private function __checkOrderPayments( $fields, Order $order = null, Customer $customer  )
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
            $currenctPayments   =   collect( $fields[ 'payments' ] )
                ->map( fn( $payment ) => $payment[ 'id' ] ?? false )
                ->filter( fn( $payment ) => $payment !== false )
                ->toArray();
            
            $order->payments->each( function( $payment ) use ( $currenctPayments ) {
                if ( ! in_array( $payment->id, $currenctPayments ) ) {
                    throw new NotAllowedException( __( 'Unable to proceed as one of the previous submitted payment is missing from the order.' ) );
                }
            });

            /**
             * if the order was no more "hold"
             * we shouldn't allow the order to switch to hold.
             */
            if ( $order->payment_status !== Order::PAYMENT_HOLD && isset( $fields[ 'payment_status' ] ) && $fields[ 'payment_status' ] === Order::PAYMENT_HOLD ) {
                throw new NotAllowedException( __( 'The order payment status cannot switch to hold as a payment has already been made on that order.' ) );
            }
        }

        
        $totalPayments  =   0;

        $subtotal       =   collect( $fields[ 'products' ] )->map(function ($product) {
            return floatval($product['total_price']);
        })->sum();

        $total          =   $this->currencyService->define( 
                $subtotal + $this->__getShippingFee($fields) 
            )
            ->subtractBy( ( $fields[ 'discount' ] ?? $this->computeDiscountValues( $fields[ 'discount_percentage' ] ?? 0, $subtotal ) ) )
            ->subtractBy( $this->__computeOrderCoupons( $fields, $subtotal ) )
            ->getRaw();

        $allowedPaymentsGateways    =   config('nexopos.pos.payments');

        if ( ! empty( $fields[ 'payments' ] ) ) {
            foreach ( $fields[ 'payments' ] as $payment) {
                

                if (in_array($payment['identifier'], array_keys($allowedPaymentsGateways))) {
                    
                    /**
                     * check if the customer account are enough for the account-payment
                     * when that payment is provided
                     */
                    if ( $payment[ 'identifier' ] === 'account-payment' && $customer->account_amount < floatval( $payment[ 'value' ] ) ) {
                        throw new NotAllowedException( __( 'The customer account funds are\'nt enough to process the payment.' ) );
                    }

                    $totalPayments  =   $this->currencyService->define($totalPayments)
                        ->additionateBy($payment['value'])
                        ->get();

                } else {
                    throw new NotAllowedException( __('Unable to proceed. One of the submitted payment type is not supported.') );
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
                throw new NotAllowedException([
                    'status'    =>  'failed',
                    'message'   =>  __('Unable to proceed. Partially paid orders aren\'t allowed. This option could be changed on the settings.')
                ]);
            } else if (
                $this->optionsService->get('ns_orders_allow_incomplete', true) === false &&
                $totalPayments === 0
            ) {
                throw new NotAllowedException([
                    'status'    =>  'failed',
                    'message'   =>  __('Unable to proceed. Unpaid orders aren\'t allowed. This option could be changed on the settings.')
                ]);
            }
        }

        if ( $totalPayments >= $total ) {
            $paymentStatus      =   Order::PAYMENT_PAID;
        } else if ($totalPayments < $total && $totalPayments > 0) {
            $paymentStatus      =   Order::PAYMENT_PARTIALLY;
        } else if ( $totalPayments === 0 && ( ! isset( $fields[ 'payment_status' ] ) || ( $fields[ 'payment_status' ] !== Order::PAYMENT_HOLD ) ) ) {
            $paymentStatus      =   Order::PAYMENT_UNPAID;
        } else if ( $totalPayments === 0 && ( isset( $fields[ 'payment_status' ] ) && ( $fields[ 'payment_status' ] === Order::PAYMENT_HOLD ) ) ){
            $paymentStatus      =   Order::PAYMENT_HOLD;
        }

        return [
            'payments'          =>  $fields['payments'] ?? [],
            'total'             =>  $total,
            'totalPayments'     =>  $totalPayments,
            'paymentStatus'     =>  $paymentStatus
        ];
    }

    private function __computeOrderTotal($data)
    {
        /**
         * @param float $order 
         * @param float $subTotal 
         * @param float $taxes
         * @param float $totalPayments
         * @param string $paymentStatus
         */
        extract($data);

        /**
         * increase the total with the
         * shipping fees and subtract the discounts
         */
        $order->total           =   $this->currencyService->define( $order->subtotal )
            ->additionateBy( $order->shipping )
            ->subtractBy( $order->total_coupons )
            ->subtractBy( $order->discount )
            ->get();

        $order->tax_value       =   $taxes;
        $order->gross_total     =   $order->total;

        /**
         * compute change
         */
        $order->change          =   $this->currencyService->define( $order->tendered )
            ->subtractBy( $order->total )
            ->get();

        /**
         * compute gross total
         */
        $order->net_total     =   $this->currencyService->define( $order->subtotal )
            ->subtractBy( $order->discount )
            ->subtractBy( $order->total_coupons )
            ->subtractBy( $taxes )
            ->get();

        return $order;
    }

    /**
     * @param Order order instance
     * @param array<OrderProduct> array of products
     * @return array [$total, $taxes, $order]
     */
    private function __saveOrderProducts($order, $products)
    {
        $subTotal       =   0;
        $taxes          =   0;
        $gross          =   0;

        $products->each(function ($product) use (&$subTotal, &$taxes, &$order, &$gross) {

            /**
             * this should run only if the product looped doesn't include an identifier.
             * Usually if it's the case, the product is supposed to have been already handled before.
             */
            // if ( empty( $product[ 'id' ] ) ) {
            // }
            
            /**
             * storing the product
             * history as a sale
             */
            $history                    =   [
                'order_id'      =>  $order->id,
                'unit_id'       =>  $product[ 'unit_id' ],
                'product_id'    =>  $product[ 'product' ]->id,
                'quantity'      =>  $product[ 'quantity' ],
                'unit_price'    =>  $this->currencyService->getRaw( $product[ 'unit_price' ] ),
                'total_price'   =>  $this->currencyService->define($product[ 'unit_price' ])
                    ->multiplyBy($product['quantity'])
                    ->get()
            ];

            /**
             * if the product id is provided
             * then we can use that id as a reference. 
             */            
            if ( isset( $product[ 'id' ] ) ) {
                $orderProduct           =   OrderProduct::find( $product[ 'id' ] );
            } else {
                $orderProduct           =   new OrderProduct;
            }

            $orderProduct->order_id                     =   $order->id;
            $orderProduct->unit_quantity_id             =   $product[ 'unit_quantity_id' ]; 
            $orderProduct->unit_name                    =   $product[ 'unit_name' ] ?? Unit::find( $product[ 'unit_id' ] )->name; 
            $orderProduct->unit_id                      =   $product[ 'unit_id' ];
            $orderProduct->product_id                   =   $product[ 'product' ]->id;
            $orderProduct->product_category_id          =   $product[ 'product' ]->category_id;
            $orderProduct->name                         =   $product[ 'product' ]->name;
            $orderProduct->quantity                     =   $history[ 'quantity'];

            /**
             * We might need to have another consideration
             * on how we do compute the taxes
             */
            if ( $product[ 'product' ][ 'tax_type' ] !== 'disabled' && ! empty( $product[ 'product' ]->tax_group_id )) {
                $orderProduct->tax_group_id         =   $product[ 'product' ]->tax_group_id;
                $orderProduct->tax_type             =   $product[ 'product' ]->tax_type;
                $orderProduct->tax_value            =   $product[ 'tax_value' ];
            }

            $orderProduct->unit_price           =   $this->currencyService->define( $product[ 'unit_price' ] )->getRaw();
            $orderProduct->net_price            =   $this->currencyService->define( $product[ 'unitQuantity' ]->incl_tax_sale_price )->getRaw();
            $orderProduct->gross_price          =   $this->currencyService->define( $product[ 'unitQuantity' ]->excl_tax_sale_price )->getRaw();
            $orderProduct->discount_type        =   $product[ 'discount_type' ] ?? 'none';
            $orderProduct->discount             =   $product[ 'discount' ] ?? 0;
            $orderProduct->discount_percentage  =   $product[ 'discount_percentage' ] ?? 0;
            $orderProduct->total_purchase_price =   $this->currencyService->define( $product[ 'total_purchase_price' ] ?? 0 )
                ->subtractBy( $orderProduct->discount )
                // ->subtractBy( $orderProduct->tax_value ?? 0 ) maybe we need to create a gross_purchase_price and net_purchase_price fields
                ->getRaw();

            $this->computeOrderProduct( $orderProduct );

            $orderProduct->save();

            $subTotal  =   $this->currencyService->define($subTotal)
                ->additionateBy($orderProduct->total_price)
                ->get();

            /**
             * if the settins allow computing product taxes
             * we the increment the taxes value accordingly
             */
            if ( in_array( ns()->option->get( 'ns_pos_vat' ), [ 'products_vat', 'products_flat_vat', 'products_variable_vat' ] ) ) {
                $taxes  =   $this->currencyService->define($taxes)
                    ->additionateBy($product[ 'tax_value' ])
                    ->get();
            }

            if ( in_array( $order[ 'payment_status' ], [ 'paid', 'partially_paid', 'unpaid' ] ) ) {
                $this->productService->stockAdjustment( ProductHistory::ACTION_SOLD, $history );
            }

            event( new OrderProductAfterSavedEvent( $orderProduct, $order, $product ) );
        });

        return compact('subTotal', 'taxes', 'order');
    }

    private function __buildOrderProducts( $products )
    {
        return collect( $products )->map( function( $orderProduct ) {
            $product    =   Cache::remember( 'store-' . ( $orderProduct['product_id'] ?? $orderProduct['sku'] ), 60, function() use ($orderProduct) {
                if (!empty(@$orderProduct['product_id'])) {
                    return $this->productService->get($orderProduct['product_id']);
                } else if (!empty(@$orderProduct['sku'])) {
                    return $this->productService->getProductUsingSKUOrFail($orderProduct['sku']);
                }
            });
            
            $productUnitQuantity    =   ProductUnitQuantity::findOrFail( $orderProduct[ 'unit_quantity_id' ] );

            $orderProduct           =   $this->__buildOrderProduct(
                $orderProduct,
                $productUnitQuantity,
                $product
            );

            return $orderProduct;
        });
    }

    /**
     * @param array of orderProduct
     */
    private function __checkProductStock( $items )
    {
        $session_identifier         =   Str::random( '10' );

        /**
         * here comes a loop.
         * We'll been fetching from the database
         * we need somehow to integrate a cache
         * we'll also populate the unit for the item 
         * so that it can be reused 
         */
        $items  =  collect($items)->map( function ( array $orderProduct ) use ( $session_identifier ) {
            $this->checkQuantityAvailability( 
                $orderProduct[ 'product' ], 
                $orderProduct[ 'unitQuantity' ],
                $orderProduct,
                $session_identifier
            );

            return $orderProduct;
        });

        OrderAfterProductStockCheckedEvent::dispatch( $items, $session_identifier );

        return $items;
    }

    /**
     * Prebuild a product that will be processed
     * @param array Order Product
     * @param ProductUnitQuantity $productUnitQuantity
     * @param Product $product
     * @return array Order Product (updated)
     */
    public function __buildOrderProduct( array $orderProduct, ProductUnitQuantity $productUnitQuantity, Product $product )
    {       
        /**
         * This will calculate the product default field
         * when they aren't provided. 
         */
        $orderProduct                           =   $this->computeProduct( $orderProduct, $product, $productUnitQuantity );
        $orderProduct[ 'unit_id' ]              =   $productUnitQuantity->unit->id;
        $orderProduct[ 'unit_quantity_id' ]     =   $productUnitQuantity->id;
        $orderProduct[ 'total_price' ]          =   $orderProduct[ 'total_price' ];
        $orderProduct[ 'product' ]              =   $product;
        $orderProduct[ 'unitQuantity' ]         =   $productUnitQuantity;

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
                $storageQuantity        =   OrderStorage::withIdentifier( $session_identifier )
                    ->withProduct( $product->id )
                    ->withUnitQuantity( $orderProduct[ 'unit_quantity_id' ] )
                    ->sum( 'quantity' );

                if ( $productUnitQuantity->quantity - $storageQuantity < $orderProduct[ 'quantity' ] ) {
                    throw new \Exception( 
                        sprintf( 
                            __( 'Unable to proceed, there is not enough stock for %s using the unit %s. Requested : %s, available %s' ),
                            $product->name,
                            $productUnitQuantity->unit->name, 
                            $orderProduct[ 'quantity' ],
                            $productUnitQuantity->quantity - $storageQuantity
                        )
                    );
                }

                /**
                 * We keep reference on the database
                 * that's more easier.
                 */
                $storage                                =   new OrderStorage;
                $storage->product_id                    =   $product->id;
                $storage->unit_id                       =   $productUnitQuantity->unit->id;
                $storage->unit_quantity_id              =   $orderProduct[ 'unit_quantity_id' ];
                $storage->quantity                      =   $orderProduct[ 'quantity' ];
                $storage->session_identifier            =   $session_identifier;
                $storage->save();

            } catch (NotFoundException $exception) {
                throw new \Exception(
                    sprintf(
                        __('Unable to proceed, the product "%s" has a unit which cannot be retreived. It might have been deleted.'),
                        $product->name
                    )
                );
            }
        }
    }

    public function computeProduct( $fields, Product $product, ProductUnitQuantity $productUnitQuantity )
    {
        $sale_price     =   ( $fields[ 'unit_price' ] ?? $productUnitQuantity->sale_price );

        /**
         * if the discount value wasn't provided, it would have
         * been calculated based on the "discount_percentage" & "discount_type"
         * informations.
         */
        if ( 
            isset( $fields[ 'discount_percentage' ] ) && 
            isset( $fields[ 'discount_type' ] ) && 
            $fields[ 'discount_type' ] === 'percentage' &&
            empty( $fields[ 'discount' ] ) ) {
                $fields[ 'discount' ]       =   ( $fields[ 'discount' ] ?? ( $sale_price * $fields[ 'discount_percentage' ] ) / 100 );
        } else {
            $fields[ 'discount' ]       =   $fields[ 'discount' ] ?? 0;
        }

        /**
         * if the item is assigned to a tax group
         * it should compute the tax otherwise
         * the value is "0".
         */
        if ( empty( $fields[ 'tax_value' ] ) ) {
            $fields[ 'tax_value' ]      =   $this->currencyService->define(
                $this->taxService->getComputedTaxGroupValue(
                    $product->tax_type,
                    $product->tax_group_id,
                    $sale_price
                )
            )
            ->multiplyBy( floatval( $fields[ 'quantity' ] ) )
            ->getRaw();        
        }

        /**
         * If the total_price is not defined
         * let's compute that
         */
        if ( empty( $fields[ 'total_price' ] ) ) {
            $fields[ 'total_price' ]    =   ( 
                $sale_price - 
                $fields[ 'discount' ]
            ) * floatval( $fields[ 'quantity' ] );
        }

        /**
         * We'll retreive the last defined purchase price
         * for the defined item. Won't work for unmaterial item
         */
        $procurementProduct     =   ProcurementProduct::where( 'product_id', $product->id )
            ->where( 'unit_id', $productUnitQuantity->unit_id )
            ->orderBy( 'id', 'desc' )
            ->first();

        /**
         * @todo we might check if the barcode provided
         * here include a procurement id
         */
        if ( $procurementProduct instanceof ProcurementProduct ) {
            $fields[ 'total_purchase_price' ]       =   $this->currencyService->define( $procurementProduct->purchase_price )
                ->multiplyBy( $fields[ 'quantity' ] )
                ->getRaw();
        }


        return $fields;
    }

    /**
     * @todo we need to be able to
     * change the code format
     */
    public function generateOrderCode()
    {
        $now        =   $this->dateService->now()->toDateString();
        $count      =   DB::table('nexopos_orders_count')
            ->where('date', $now)
            ->value('count');

        if ($count === null) {
            $count  =   1;
            DB::table('nexopos_orders_count')
                ->insert([
                    'date'      =>  $now,
                    'count'     =>  $count
                ]);
        }

        DB::table('nexopos_orders_count')
            ->where('date', $now)
            ->increment('count');

        $carbon     =   $this->dateService->now();

        return $carbon->year . '-' . str_pad($carbon->month, 2, STR_PAD_LEFT) . '-' . str_pad($carbon->day, 2, STR_PAD_LEFT) . '-' . str_pad($count, 3, 0, STR_PAD_LEFT);
    }

    private function __initOrder( $fields, $paymentStatus, $order )
    {
        /**
         * if the order is not provided as a parameter
         * a new instance is initialized.
         */
        if ( ! $order instanceof Order ) {
            $order                      =   new Order;
        }

        /**
         * let's save the order at 
         * his initial state
         */
        $order->customer_id             =   $fields['customer_id'];
        $order->shipping                =   $this->currencyService->getRaw( $fields[ 'shipping' ] ?? 0 ); // if shipping is not provided, we assume it's free
        $order->subtotal                =   $this->currencyService->getRaw( $fields[ 'subtotal' ] ?? 0 ) ?: $this->computeSubTotal( $fields, $order );
        $order->discount_type           =   $fields['discount_type'] ?? null;
        $order->discount_percentage     =   $this->currencyService->getRaw( $fields['discount_percentage'] ?? 0 );
        $order->discount                =   $this->currencyService->getRaw( $fields['discount'] ?? 0 ) ?: $this->computeOrderDiscount( $order, $fields );
        $order->total                   =   $this->currencyService->getRaw( $fields[ 'total' ] ?? 0 ) ?: $this->computeTotal( $fields, $order );
        $order->type                    =   $fields['type']['identifier'];
        $order->final_payment_date      =   $fields['final_payment_date' ] ?? null; // when the order is not saved as laid away
        $order->total_instalments       =   $fields['total_instalments' ] ?? 0;
        $order->register_id             =   $fields['register_id' ] ?? null;
        $order->note                    =   $fields['note'] ?? null;
        $order->note_visibility         =   $fields['note_visibility' ] ?? null;
        $order->tax_group_id            =   $fields['tax_group_id' ] ?? null;
        $order->tax_type                =   $fields['tax_type' ] ?? null;
        $order->total_coupons           =   $fields['total_coupons'] ?? 0;
        $order->payment_status          =   $paymentStatus;
        $order->delivery_status         =   'pending';
        $order->process_status          =   'pending';
        $order->author                  =   Auth::id();
        $order->title                   =   $fields[ 'title' ] ?? null;
        $order->tax_value               =   $this->currencyService->getRaw( $fields[ 'tax_value' ] ?? 0 ) ?: $this->computeOrderTaxValue( $fields, $order );
        $order->code                    =   $order->code ?? $this->generateOrderCode(); // to avoid generating a new code
        $order->save();

        return $order;
    }

    /**
     * Compute the discount data
     * @param array $fields
     * @return int $discount
     */
    public function computeOrderDiscount( $order, $fields = [] )
    {
        $fields[ 'discount_type' ]          =   $fields[ 'discount_type' ] ?? $order->discount_type;
        $fields[ 'discount_percentage' ]    =   $fields[ 'discount_percentage' ] ?? $order->discount_percentage;
        $fields[ 'discount' ]               =   $fields[ 'discount' ] ?? $order->discount;
        $fields[ 'subtotal' ]               =   $fields[ 'subtotal' ] ?? $order->subtotal;
        $fields[ 'discount' ]               =   $fields[ 'discount' ] ?? $order->discount ?? 0;

        if ( ! empty( $fields[ 'discount_type' ] ) && ! empty( $fields[ 'discount_percentage' ] ) && $fields[ 'discount_type' ] === 'percentage' ) {
            return $this->currencyService->define( $fields[ 'subtotal' ] )
                ->multiplyBy( $fields[ 'discount_percentage' ] )
                ->divideBy( 100 )
                ->getRaw();
        } else {
            return $this->currencyService->getRaw( $fields[ 'discount' ] );
        }
    }

    public function computeOrderTaxValue( $fields, $order )
    {
        return $this->currencyService->getRaw( $fields[ 'products' ]->map( fn( $product ) => $product[ 'tax_value' ] )->sum() );
    }

    public function computeTotal( $fields, $order )
    {
        return $this->currencyService->define( $order->subtotal )
            ->subtractBy( $order->discount )
            ->additionateBy( $order->shipping )
            ->getRaw();
    }

    public function computeSubTotal( $fields, $order )
    {
        return $this->currencyService->getRaw( collect( $fields[ 'products' ] )
            ->map( fn( $product ) => floatval( $product[ 'total_price' ] ) )
            ->sum() );
    }

    private function __customerIsDefined($fields)
    {
        try {
            return $this->customerService->get($fields['customer_id']);
        } catch (NotFoundException $exception) {
            throw new NotFoundException([
                'status'    =>  'failed',
                'message'   =>  __('Unable to find the customer using the provided ID. The order creation has failed.')
            ]);
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
            throw new NotAllowedException( __('Unable to proceed a refund on an unpaid order.') );
        }

        $orderRefund                    =   new OrderRefund();
        $orderRefund->author            =   Auth::id();
        $orderRefund->order_id          =   $order->id;
        $orderRefund->payment_method    =   $fields[ 'payment' ][ 'identifier' ];
        $orderRefund->shipping          =   ( isset( $fields[ 'refund_shipping' ] ) && $fields[ 'refund_shipping' ] ? $order->shipping : 0 );
        $orderRefund->total             =   $this->currencyService->getRaw( $fields[ 'total' ] );
        $orderRefund->save();

        event( new OrderRefundPaymentAfterCreatedEvent( $orderRefund ) );

        $results                =   [];

        foreach( $fields[ 'products' ] as $product ) {
            $results[]   =   $this->refundSingleProduct( $order, OrderProduct::find( $product[ 'id' ] ), $product );
        }

        /**
         * if the shipping is refunded
         * We'll do that here
         */
        if ( isset( $fields[ 'refund_shipping' ] ) && $fields[ 'refund_shipping' ] === true ) {
            $order->shipping        =       0;
            $order->save();
        }

        /**
         * check if the payment used is the customer account
         * so that we can withdraw the funds to the account
         */
        if ( $fields[ 'payment' ][ 'identifier' ] === OrderPayment::PAYMENT_ACCOUNT ) {
            $this->customerService->saveTransaction(
                $order->customer,
                CustomerAccountHistory::OPERATION_REFUND,
                $fields[ 'total' ],
                __( 'The current credit has been issued from a refund.' )
            );
        }

        event( new OrderAfterRefundedEvent( $order, $orderRefund ) );

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The order has been successfully refunded.' ),
            'data'      =>  compact( 'results', 'order', 'orderRefund' )
        ];
    }

    /**
     * Refund a product from an order
     * @param int product id
     * @param string status : sold, returned, defective
     */
    public function refundSingleProduct( Order $order, OrderProduct $orderProduct, $details )
    {
        if ( ! in_array( $details[ 'condition' ], [
            OrderProductRefund::CONDITION_DAMAGED,
            OrderProductRefund::CONDITION_UNSPOILED
        ] ) ) {
            throw new NotAllowedException([
                'status'    =>  'failed',
                'message'   =>  __( 'unable to proceed to a refund as the provided status is not supported.' )
            ]);
        }

        if ( ! in_array( $order->payment_status, [
            Order::PAYMENT_PARTIALLY,
            Order::PAYMENT_PARTIALLY_REFUNDED,
            Order::PAYMENT_UNPAID,
            Order::PAYMENT_PAID
        ] ) ) {
            throw new NotAllowedException( __( 'Unable to proceed a refund on an unpaid order.' ) );
        }

        /**
         * proceeding a refund should reduce the quantity 
         * available on the order for a specific product.
         */
        $orderProduct->status           =   'returned';
        $orderProduct->quantity         -=   floatval( $details[ 'quantity' ] );
        $this->computeOrderProduct( $orderProduct );
        $orderProduct->save();

        $productRefund                      =   new OrderProductRefund;
        $productRefund->condition           =   $details[ 'condition' ];
        $productRefund->description         =   $details[ 'description' ];
        $productRefund->unit_price          =   $details[ 'unit_price' ];
        $productRefund->unit_id             =   $orderProduct->unit_id;
        $productRefund->total_price         =   $this->currencyService->getRaw( $productRefund->unit_price * floatval( $details[ 'quantity' ] ) );
        $productRefund->quantity            =   $details[ 'quantity' ];
        $productRefund->author              =   Auth::id();
        $productRefund->order_id            =   $order->id;
        $productRefund->order_product_id    =   $orderProduct->id;
        $productRefund->product_id          =   $orderProduct->product_id;

        event( new OrderAfterProductRefundedEvent( $order, $orderProduct ) );

        /**
         * we do proceed by doing an initial return
         */
        $this->productService->stockAdjustment( ProductHistory::ACTION_RETURNED, [
            'total_price'       =>  $productRefund->total_price,
            'quantity'          =>  $productRefund->quantity,
            'unit_price'        =>  $productRefund->unit_price,
            'product_id'        =>  $productRefund->product_id,
            'unit_id'           =>  $productRefund->unit_id,
            'order_id'          =>  $order->id
        ]);

        /**
         * If the returned stock is damaged
         * then we can pull this out from the stock
         */
        if ( $details[ 'condition' ] === OrderProductRefund::CONDITION_DAMAGED ) {
            $this->productService->stockAdjustment( ProductHistory::ACTION_DEFECTIVE, [
                'total_price'       =>  $productRefund->total_price,
                'quantity'          =>  $productRefund->quantity,
                'unit_price'        =>  $productRefund->unit_price,
                'product_id'        =>  $productRefund->product_id,
                'unit_id'           =>  $productRefund->unit_id,
                'order_id'          =>  $order->id
            ]);
        }

        return [
            'status'    =>  'success',
            'message'   =>  __('The product %s has been successfully refunded.'),
            'data'      =>  compact( 'productRefund', 'orderProduct' )
        ];
    }

    /**
     * this method computes total for the current provided
     * order product
     * @param OrderProduct $orderProduct
     * @return void
     */
    public function computeOrderProduct( OrderProduct $orderProduct )
    {
        /**
         * let's compute the discount
         * for that specific product
         */
        $total_gross_discount   =   ( float ) $orderProduct->discount;
        $total_discount         =   ( float ) $orderProduct->discount;
        $total_net_discount     =   ( float ) $orderProduct->discount;

        if ( $orderProduct->discount_type === 'percentage' ) {
            $total_gross_discount       =   $this->computeDiscountValues( 
                $orderProduct->discount_percentage,
                $orderProduct->total_gross_price
            );

            $total_discount             =   $this->computeDiscountValues( 
                $orderProduct->discount_percentage,
                $orderProduct->total_gross_price
            );

            $total_net_discount         =   $this->computeDiscountValues( 
                $orderProduct->discount_percentage,
                $orderProduct->total_gross_price
            );
        }

        $orderProduct->total_gross_price    =   $this->currencyService
            ->define( $orderProduct->excl_tax_sale_price )
            ->multiplyBy( $orderProduct->quantity )
            ->subtractBy( $total_gross_discount )
            ->get();

        $orderProduct->total_price          =   $this->currencyService
            ->define( $orderProduct->unit_price )
            ->multiplyBy( $orderProduct->quantity )
            ->subtractBy( $total_discount )
            ->get();

        $orderProduct->total_net_price      =   $this->currencyService
            ->define( $orderProduct->incl_tax_sale_price )
            ->multiplyBy( $orderProduct->quantity )
            ->subtractBy( $total_net_discount )
            ->get();
    }

    /**
     * compute a discount value using
     * provided values
     * @param float $rate
     * @param float $value
     * @return float
     */
    public function computeDiscountValues( $rate, $value )
    {
        if ( $rate > 0 ) {
            return ( $value * $rate ) / 100;
        }

        return 0;
    }

    /**
     * Return a single order product
     * @param int product id
     * @return OrderProduct
     */
    public function getOrderProduct($product_id)
    {
        $product    =   OrderProduct::find($product_id);

        if (!$product instanceof OrderProduct) {
            throw new NotFoundException([
                'status'    =>  'failed',
                'message'   =>  __('Unable to find the order product using the provided id.')
            ]);
        }

        return $product;
    }

    /**
     * Get order products
     * @param mixed identifier
     * @param string pivot
     * @return Collection
     */
    public function getOrderProducts($identifier, $pivot = 'id')
    {
        return $this->getOrder($identifier, $pivot)->products()->with( 'unit' )->get();
    }

    /**
     * return a specific 
     * order using a provided identifier and pivot
     * @param mixed identifier
     * @param string pivot
     * @return Order
     */
    public function getOrder($identifier, $as = 'id')
    {
        if (in_array($as, ['id', 'code'])) {
            $order  =   Order::where($as, $identifier)
                ->with( 'payments' )
                ->with( 'shipping_address' )
                ->with( 'billing_address' )
                ->with( 'taxes' )   
                ->with( 'instalments' )
                ->with( 'coupons' )
                ->with( 'products.unit' )
                ->with( 'products.product.unit_quantities' )
                ->with( 'customer' )
                ->first();

            if ( ! $order instanceof Order ) {
                throw new NotFoundException( sprintf(
                    __('Unable to find the requested order using "%s" as pivot and "%s" as identifier'),
                    $as,
                    $identifier
                ) );
            }

            $order->products;

            Hook::action( 'ns-load-order', $order );

            return $order;
        }

        throw new NotAllowedException([
            'status'    =>  'failed',
            'message'   =>  __('Unable to fetch the order as the provided pivot argument is not supported.')
        ]);
    }

    /**
     * Get all the order that has been
     * already created
     * @param void
     * @return array of orders
     */
    public function getOrders($filter = 'mixed')
    {
        if (in_array($filter, ['paid', 'unpaid', 'refunded'])) {
            return Order::where('payment_status', $filter)
                ->get();
        }
        return Order::get();
    }

    /**
     * Adding a product to an order
     * @param Order order
     * @param array product
     * @return array response
     */
    public function addProducts(Order $order, $products)
    {
        $products   =   $this->__checkProductStock($products);

        /**
         * let's save the products
         * to the order now as the stock
         * seems to be okay
         */
        $this->__saveOrderProducts($order, $products);

        /**
         * Now we should refresh the order
         * to have the total computed
         */
        $this->refreshOrder($order);

        return [
            'status'    =>  'success',
            'message'   =>  sprintf(
                __('The product has been added to the order "%s"'),
                $order->code
            )
        ];
    }

    /**
     * refresh an order by computing
     * all the product total, taxes and
     * shipping all together.
     * @param Order
     * @return array repsonse
     * @todo test required
     */
    public function refreshOrder(Order $order)
    {
        $products               =   $this->getOrderProducts($order->id);

        $productTotal           =   $products->map(function ($product) {
            return floatval($product->total_price);
        })->sum();

        $productGrossTotal      =   $products->map(function ($product) {
            return floatval($product->total_gross_price);
        })->sum();

        $productsTotalTaxes     =   $products->map(function ($product) {
            return floatval($product->tax_value);
        })->sum();

        $orderShipping          =   $order->shipping;
        $totalPayments          =   $order->payments->map( fn( $payment ) => $payment->value )->sum();
        $order->tendered        =   $totalPayments;

        /**
         * let's refresh all the order values
         */
        $order->subtotal        =   $productGrossTotal;
        $order->gross_total     =   $productGrossTotal;
        $order->discount        =   $this->computeOrderDiscount( $order );
        $order->total           =   $productTotal + $orderShipping;
        $order->tax_value       =   $productsTotalTaxes;
        $order->change          =   $order->tendered - $order->total;

        $refunds                =   $order->refund;
        $totalRefunds           =   $refunds->map( fn( $refund ) => $refund->total )->sum();

        if ( ( float ) $order->total == 0 && $totalRefunds > 0 ) {
            $order->payment_status      =       Order::PAYMENT_REFUNDED;
        } else if ( $order->total > 0 && $totalRefunds > 0 ) {
            $order->payment_status      =       Order::PAYMENT_PARTIALLY_REFUNDED;
        } else if ( $order->tendered >= $order->total && $order->total > 0 ) {
            $order->payment_status      =       Order::PAYMENT_PAID;
        } else if ( $order->tendered < $order->total ) {
            $order->payment_status      =       Order::PAYMENT_PARTIALLY;
        } else if ( $order->total == 0 && $totalRefunds == 0 ) {
            $order->payment_status      =       Order::PAYMENT_UNPAID;
        }

        $order->save();

        event( new OrderAfterUpdatedEvent( $order ) );

        return [
            'status'    =>  'success',
            'message'   =>  __('the order has been succesfully computed.'),
            'data'      =>  compact('order')
        ];
    }

    /**
     * Delete a specific order
     * and make product adjustment
     * @param Order order
     * @return array response
     */
    public function deleteOrder(Order $order)
    {
        event( new OrderBeforeDeleteEvent( $order ) );

        $order->products->each( function( OrderProduct $product) {
            /**
             * we do proceed by doing an initial return
             */
            $this->productService->stockAdjustment( ProductHistory::ACTION_DELETED, [
                'total_price'       =>  $product->total_price,
                'product_id'        =>  $product->product_id,
                'unit_id'           =>  $product->unit_id,
                'quantity'          =>  $product->quantity,
                'unit_price'        =>  $product->unit_price
            ]);

            $product->delete();
        });

        $order->delete();

        event( new OrderAfterDeletedEvent( $order ) );

        return [
            'status'    =>  'success',
            'message'   =>  __('The order has been deleted.' )
        ];
    }

    /**
     * Delete a product that is included 
     * within a specific order and refresh the order
     * @param Order order instance
     * @param int product id
     * @return array response
     */
    public function deleteOrderProduct(Order $order, $product_id)
    {
        $hasDeleted     =   false;

        $order->products->map(function ($product) use ( $product_id, &$hasDeleted, $order ) {
            if ($product->id === intval($product_id)) {

                event( new OrderBeforeDeleteProductEvent( $order, $product));

                $product->delete();
                $hasDeleted     =   true;
            }
        });

        if ($hasDeleted) {
            $this->refreshOrder($order);

            return [
                'status'    =>  'success',
                'message'   =>  __('The product has been successfully deleted from the order.')
            ];
        }

        throw new NotFoundException([
            'status'    =>  'failed',
            'message'   =>  __('Unable to find the requested product on the provider order.')
        ]);
    }

    /**
     * get orders payments
     * @param int order id
     * @return array of payments
     */
    public function getOrderPayments($orderID)
    {
        $order  =   $this->getOrder($orderID);
        return $order->payments;
    }

    /**
     * It only returns what is the type of
     * the orders
     * @param string
     * @return string
     */
    public function getTypeLabel( $type ) 
    {
        switch( $type ) {
            case 'delivery': return __( 'Delivery' ); break;
            case 'takeaway': return __( 'Take Away' ); break;
        }
    }

    /**
     * It only returns what is the type of
     * the orders
     * @param string
     * @return string
     */
    public function getPaymentLabel( $type ) 
    {
        $payments   =   config( 'nexopos.orders.statuses' );
        return $payments[ $type ] ?? sprintf( __( 'Unknown Status (%s)' ), $type );
    }

    /**
     * It only returns what is the type of
     * the orders
     * @param string
     * @return string
     */
    public function getShippingLabel( $type ) 
    {
        switch( $type ) {
            case 'pending': return __( 'Pending' ); break;
            case 'ongoing': return __( 'Ongoing' ); break;
            case 'delivered': return __( 'Delivered' ); break;
            case 'failed': return __( 'Shipping Failed' ); break;
            default : return sprintf( _( 'Unknown Status (%s)' ), $type ); break;
        }
    }

    /**
     * parse and render options template
     * based on the provided values
     * @param array options
     * @param Order $order
     * @return string
     */
    public function orderTemplateMapping( $option, Order $order )
    {
        $template                       =   $this->optionsService->get( $option );
        $availableTags                  =   [
            "store_name"                =>  $this->optionsService->get( 'ns_store_name' ),
            "store_email"               =>  $this->optionsService->get( 'ns_store_email' ),
            "store_phone"               =>  $this->optionsService->get( 'ns_store_phone' ),
            "cashier_name"              =>  $order->user->username,
            "cashier_id"                =>  $order->author,
            "order_code"                =>  $order->code,
            "order_date"                =>  $order->created_at,
            "customer_name"             =>  $order->customer->name,
            "customer_email"            =>  $order->customer->email,
            "shipping_" . "name"        =>  $order->shipping_address->name,
            "shipping_" . "surname"     =>  $order->shipping_address->surname,
            "shipping_" . "phone"       =>  $order->shipping_address->phone,
            "shipping_" . "address_1"   =>  $order->shipping_address->address_1,
            "shipping_" . "address_2"   =>  $order->shipping_address->address_2,
            "shipping_" . "country"     =>  $order->shipping_address->country,
            "shipping_" . "city"        =>  $order->shipping_address->city,
            "shipping_" . "pobox"       =>  $order->shipping_address->pobox,
            "shipping_" . "company"     =>  $order->shipping_address->company,
            "shipping_" . "email"       =>  $order->shipping_address->email,
            "billing_" . "name"         =>  $order->billing_address->name,
            "billing_" . "surname"      =>  $order->billing_address->surname,
            "billing_" . "phone"        =>  $order->billing_address->phone,
            "billing_" . "address_1"    =>  $order->billing_address->address_1,
            "billing_" . "address_2"    =>  $order->billing_address->address_2,
            "billing_" . "country"      =>  $order->billing_address->country,
            "billing_" . "city"         =>  $order->billing_address->city,
            "billing_" . "pobox"        =>  $order->billing_address->pobox,
            "billing_" . "company"      =>  $order->billing_address->company,
            "billing_" . "email"        =>  $order->billing_address->email,
        ];

        foreach( $availableTags as $tag => $value ) {
            $template   =   ( str_replace( '{'.$tag.'}', $value, $template ) );
        }

        return $template;
    }

    /**
     * notify administrator when order
     * turned due (for layaway)
     * @return array
     */
    public function notifyExpiredLaidAway() 
    {
        $orders                 =   Order::paymentExpired()->get();

        if ( ! $orders->isEmpty() ) {
            $notificationID     =   'ns.due-orders-notifications';

            /**
             * let's clear previously emitted notification
             * with the specified identifier
             */
            Notification::identifiedBy( $notificationID )->delete();

            /**
             * @var NotificationService
             */
            $notificationService    =   app()->make( NotificationService::class );

            $notificationService->create([
                'title'         =>  __( 'Unpaid Orders Turned Due' ),
                'identifier'    =>  $notificationID,
                'url'           =>  ns()->route( 'ns.dashboard.orders' ),
                'description'   =>  sprintf( __( '%s order(s) either unpaid or partially paid has turned due. This occurs if none has been completed before the expected payment date.' ), $orders->count() )
            ])->dispatchForGroup([
                Role::namespace( 'admin' ),
                Role::namespace( 'nexopos.store.administrator' )
            ]);

            event( new DueOrdersEvent( $orders ) );

            return [
                'status'    =>  'success',
                'message'   =>  __( 'The operation was successful.' ),
            ];
        }

        return [
            'status'    =>  'failed',
            'message'   =>  __( 'No orders to handle for the moment.' )
        ];
    }

    /**
     * Void a specific order
     * by keeping a trace of what has happened.
     * @param Order
     * @param string $reason
     * @return array
     */
    public function void( Order $order, $reason )
    {
        $order->products->each( function( OrderProduct $product ) {
            /**
             * we do proceed by doing an initial return
             */
            $this->productService->stockAdjustment( ProductHistory::ACTION_VOID_RETURN, [
                'total_price'       =>  $product->total_price,
                'product_id'        =>  $product->product_id,
                'unit_id'           =>  $product->unit_id,
                'quantity'          =>  $product->quantity,
                'unit_price'        =>  $product->unit_price
            ]);
        });

        $order->payment_status      =   Order::PAYMENT_VOID;
        $order->voidance_reason     =   $reason;
        $order->save();

        event( new OrderVoidedEvent( $order ) );

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The order has been correctly voided.' )
        ];
    }

    /**
     * get orders sold during a specific perdiod
     * @param string $startDate range starts
     * @param string $endDate range ends
     * @return Collection
     */
    public function getPaidSales( $startDate, $endDate )
    {
        return Order::paid()
            ->where( 'created_at', '>=', Carbon::parse( $startDate )->startOfDay()->toDateTimeString() )
            ->where( 'created_at', '<=', Carbon::parse( $endDate )->endOfDay()->toDateTimeString() )
            ->get();
    }

    /**
     * get sold stock during a specific period
     * @param string $startDate range starts
     * @param string $endDate range ends
     * @return Collection
     */
    public function getSoldStock( $startDate, $endDate )
    {
        $rangeStarts    =   Carbon::parse( $startDate )->startOfDay()->toDateTimeString();
        $rangeEnds      =   Carbon::parse( $endDate )->endOfDay()->toDateTimeString();

        $products       =   OrderProduct::whereHas( 'order', function( Builder $query ) {
            $query->where( 'payment_status', Order::PAYMENT_PAID );
        })
            ->where( 'created_at', '>=', $rangeStarts )
            ->where( 'created_at', '<=', $rangeEnds )
            ->get();

        return $products;
    }

    public function trackOrderCoupons( Order $order )
    {
        $order->coupons->each( function( OrderCoupon $orderCoupon ) {
            $customerCoupon     =   CustomerCoupon::find( $orderCoupon->customer_coupon_id );
            
            if ( ! $customerCoupon instanceof CustomerCoupon ) {
                throw new NotFoundException( sprintf( 
                    __( 'Unable to find a reference of the provided coupon : %s' ),
                    $orderCoupon->name
                ) );
            }

            /**
             * if a limit_usage is set to 0, that means there is no limit.
             */
            if ( $customerCoupon->limit_usage > 0 ) {
                if ( $customerCoupon->usage + 1 < $customerCoupon->limit_usage ) {
                    $customerCoupon->usage      +=  1;
                    $customerCoupon->save();
                } else if ( $customerCoupon->usage + 1 === $customerCoupon->limit_usage ) {
                    $customerCoupon->usage      +=  1;
                    $customerCoupon->active     =   false;
                    $customerCoupon->save();
                }
            }
        });
    }

    /**
     * Will resolve instalments attached to an order
     * @param Order $order
     * @return void
     */
    public function resolveInstalments( Order $order )
    {
        if ( in_array( $order->payment_status, [ Order::PAYMENT_PAID, Order::PAYMENT_PARTIALLY ] ) ) {
            
            $orderInstalments       =   $order->instalments()
                ->where( 'date', '>=', ns()->date->copy()->startOfDay()->toDateTimeString() )
                ->where( 'date', '<=', ns()->date->copy()->endOfDay()->toDateTimeString() )
                ->where( 'paid', false )
                ->get();
    
            $paidInstalments        =   $order->instalments()->where( 'paid', true )->sum( 'amount' );
            $otherInstalments       =   $order->instalments()->whereNotIn( 'id', $orderInstalments->only( 'id' )->toArray() )->sum( 'amount' );
            $dueInstalments         =   Currency::raw( $orderInstalments->sum( 'amount' ) );
    
            if ( $orderInstalments->count() > 0 ) {
                $payableDifference  =   Currency::define( $order->tendered )
                    ->subtractBy( $paidInstalments )
                    ->subtractBy( $otherInstalments )
                    ->getRaw();

                if ( $dueInstalments === abs( $payableDifference ) ) {
                    $orderInstalments
                        ->each( function( $instalment ) {
                            $instalment->paid   =   true;
                            $instalment->save();
                    });
                }
            }
        }
    }

    /**
     * Will update an existing instalment
     * @param Order $order
     * @param OrderInstalment $orderInstalement
     * @param array $fields
     * @return array
     */
    public function updateInstalment( Order $order, OrderInstalment $instalment, $fields )
    {
        if ( $instalment->paid ) {
            throw new NotAllowedException( __( 'Unable to edit an already paid instalment.' ) );
        }

        foreach( $fields as $field => $value ) {
            if ( in_array( $field, [ 'date', 'amount' ] ) ) {
                $instalment->$field     =   $value;
            }
        }

        $instalment->save();

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The instalment has been saved.' ),
            'data'      =>  compact( 'instalment' )
        ];
    }

    /**
     * Will make an instalment as paid
     * @param Order $order
     * @param OrderInstalment $instalment
     * @return array
     */
    public function markInstalmentAsPaid( Order $order, OrderInstalment $instalment )
    {
        if ( $instalment->paid ) {
            throw new NotAllowedException( __( 'Unable to edit an already paid instalment.' ) );
        }

        $instalment->paid   =   true;
        $instalment->save();

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The instalment has been saved.' ),
            'data'      =>  compact( 'instalment' )
        ];
    }

    /**
     * Will delete an instalment.
     * @param OrderInstlament $instalment
     * @return array
     */
    public function deleteInstalment( Order $order, OrderInstalment $instalment )
    {
        $this->refreshInstalmentCount( $order );

        $instalment->delete();

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The instalment has been deleted.' )
        ];
    }

    public function refreshInstalmentCount( Order $order )
    {
        $order->total_instalments       =   $order->instalments()->count();
        $order->save();
    }

    /**
     * Creates an instalments
     * @param Order $order
     * @param array $fields
     * @return array
     */
    public function createInstalment( Order $order, $fields )
    {
        $totalInstalment    =   $order->instalments->map( fn( $instalment ) => $instalment->amount )->sum();

        if ( Currency::raw( $fields[ 'amount' ] ) <= 0 ) {
            throw new NotAllowedException( __( 'The defined amount is not valid.' ) );
        }

        if ( Currency::raw( $totalInstalment ) >= $order->total ) {
            throw new NotAllowedException( __( 'No further instalments is allowed for this order. The total instalment already covers the order total.' ) );
        }

        $orderInstalment                =   new OrderInstalment;
        $orderInstalment->order_id      =   $order->id;
        $orderInstalment->amount        =   $fields[ 'amount' ];
        $orderInstalment->date          =   $fields[ 'date' ];
        $orderInstalment->save();

        $this->refreshInstalmentCount( $order );

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The instalment has been created.' ),
            'data'      =>  [
                'instalment'    =>  $orderInstalment
            ]
        ];
    }

    /**
     * Changes the order processing status
     * @param Order $order
     * @param string $status
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

        $order->process_status      =   $status;
        $order->save();

        event( new OrderAfterUpdatedEvent( $order ) );

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The order has been successfully updated.' )
        ];
    }
}
