<?php
namespace App\Services;

use App\Models\Order;
use App\Services\Options;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use App\Services\DateService;
use App\Models\OrderAddress;
use App\Models\OrderPayment;
use App\Models\OrderProduct;
use App\OrderAfterCreatedEvent;
use App\OrderBeforeDeleteEvent;
use App\Services\MapperService;
use App\Services\ProductService;
use App\Services\CurrencyService;
use App\Services\CustomerService;
use App\Exceptions\NotFoundException;
use App\Models\ProductUnitQuantity;
use App\Exceptions\NotAllowedException;
use App\Events\OrderBeforeDeleteProductEvent;
use App\Events\OrderAfterProductRefundedEvent;

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

    public function __construct(
        CustomerService $customerService,
        ProductService $productService,
        UnitService $unitService,
        DateService $dateService,
        CurrencyService $currencyService,
        Options $optionsService
    )
    {
        $this->customerService  =   $customerService;
        $this->productService   =   $productService;
        $this->dateService      =   $dateService;
        $this->unitService      =   $unitService;
        $this->currencyService  =   $currencyService;
        $this->options          =   $optionsService;
    }

    public function create( $fields )
    {
        $customer   =   $this->__customerIsDefined( $fields );

        /**
         * this contains a set (array) of items
         * which has the following shape
         * @var array<OrderProduct> {
         *      'product_id'        =>  // refer to the actual product on the db
         *      'unit_id'           =>  // refer to the unit used on the product
         *      'quantity'          =>  // the actual sold quantity for the unit provided
         *      'discount'          =>  // discount value
         *      'discount_rate'     =>  // discount rate
         *      'discount_type'     =>  // either "flat" or "percentage"
         *      'unit_price'        =>  // base sale price
         *      'total_price'       =>  // total price
         *      'tax_id'            =>  // the tax that applies to the item
         *      'tax_value'         =>  // the total price of the tax
         *      'net_unit_price'    =>  // unit price with taxe over
         *      'gross_unit_price'  =>  // unit price without taxe over
         *      'net_total_price'   =>  // total unit price with taxe over
         *      'gross_total_price' =>  // total unit price without taxe over
         * }
         */
        $items      =   $this->__checkProductStock( $fields[ 'products' ] );

        /**
         * determine the value of the product 
         * on the cart and compare it along with the payment made. This will
         * help to prevent partial or quote orders
         * @param float $total
         * @param float $totalPayments
         * @param array $payments
         * @param string $paymentStatus
         */
        extract( $this->__checkOrderPayments( $items, $fields ) );

        /**
         * check delivery informations before
         * proceeding
         */
        $this->__checkAddressesInformations( $fields );

        /**
         * check discount validity and throw an
         * error is something is not set correctly.
         */
        $this->__checkDiscountVality( $fields, $items );

        /**
         * ------------------------------------------
         *                  WARNING
         * ------------------------------------------
         * all what follow will proceed database 
         * modification. All verification on current order
         * should be made prior this section
         */
        $order      =   $this->__initOrder( $fields );

        $this->__saveAddressInformations( $order, $fields );
        $this->__saveOrderPayments( $order, $payments );

        /**
         * @var Order $order
         * @var float $taxes
         * @var float $total
         */
        extract( $this->__saveOrderProducts( $order, $items ) );
        
        /**
         * compute order total
         */
        $this->__computeOrderTotal( compact( 'order', 'total', 'taxes', 'paymentStatus', 'totalPayments' ) );
        
        $this->__saveOrderDiscount( $order, $fields );

        $order->save();

        /**
         * let's notify when an
         * new order has been placed
         */
        event( new OrderAfterCreatedEvent( $order ) );

        return $order;
    }

    private function __saveOrderDiscount( $order, $fields )
    {
        /**
         * we assume this already exists
         * since it has been checked
         */
        extract( $fields[ 'discount' ] );

        if ( in_array( $type, [ 'flat', 'percentage' ] ) ) {
            $order->discount_type   =   $type;
        }

        switch( $type ) {
            case 'flat':
                $order->discount        =   $this->currencyService->define( $value )->get();
                $order->total           =   $this->currencyService->define( $order->total )
                    ->subtractBy( $order->discount )
                    ->get();
                $order->gross_total     =   $this->currencyService->define( $order->total )
                    ->subtractBy( $order->discount )
                    ->get();
            break;
            case 'percentage':
                $discountValue      =   $this->currencyService->define( $order->total )
                    ->multipliedBy( $value )
                    ->dividedBy( 100 )
                    ->get();
                $order->discount    =   $discountValue;
                $order->total       =   $this->currencyService->define( $order->total )
                    ->subtractBy( $discountValue )
                    ->get();
                $order->gross_total =   $this->currencyService->define( $order->total )
                    ->subtractBy( $discountValue )
                    ->get();
            break;
        }
    }

    /**
     * get the current shipping
     * feels
     * @param array fields
     * @return float;
     */
    private function __getShippingFee( $fields )
    {
        if ( ! empty( @$fields[ 'shipping' ] ) ) {
            $value      =   0;
            extract( $fields[ 'shipping' ] );
            
            return $value;
        }
    }

    /**
     * Check wether a discount is valid or 
     * not
     * @param array fields
     * @return void|Exception
     */
    public function __checkDiscountVality( $fields, $products )
    {
        if ( ! empty( @$fields[ 'discount' ] ) ) {
            extract( $fields[ 'discount' ]);

            if ( $type === 'percentage' && ( floatval( $value ) < 0 ) || ( floatval( $value ) > 100 )  ) {
                throw new NotAllowedException([
                    'status'    =>  'failed',
                    'message'   =>  __( 'The percentage discount provided is not valid.' )
                ]);
            } else if ( $type === 'flat' ) {
                
                $productsTotal    =   $products->map( function( $product ) {
                    return $product[ 'quantity' ] * $product[ 'product' ]->sale_price;
                })->reduce( function( $before, $after ) {
                    return $before + $after;
                });

                $shippingFees       =   $this->__getShippingFee( $fields );

                if ( $value > $productTotal + $shippingFees ) {
                    throw new NotAllowedException([
                        'status'    =>  'failed',
                        'message'   =>  __( 'A discount cannot exceed the total value of an order.' )
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
    private function __checkAddressesInformations( $fields )
    {
        $allowedKeys    =   [
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

        foreach( [ 'shipping', 'billing' ] as $type ) {
            $keys   =   array_keys( $fields[ 'addresses' ][ $type ] );
            foreach( $keys as $key ) {
                if ( ! in_array( $key, $allowedKeys ) ) {
                    throw new NotAllowedException([
                        'status'    =>  'failed',
                        'message'   =>  sprintf( __( 'Unable to proceed because the "%s" field is an unsupported attribute.' ), $key )
                    ]);
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
    private function __saveAddressInformations( $order, $fields )
    {
        foreach([ 'shipping', 'billing' ] as $type ) {
            $orderShipping          =   new OrderAddress;
            $orderShipping->type    =   $type;

            if ( ! empty ( $fields[ 'addresses' ][ $type ] ) ) {
                foreach( $fields[ 'addresses' ][ $type ] as $key => $value ) {
                    $orderShipping->$key    =   $value;
                }
    
                $orderShipping->author      =   Auth::id();
                $orderShipping->order_id    =   $order->id;
                $orderShipping->save();
            }
        }

    }

    private function __saveOrderPayments( $order, $payments )
    {
        foreach( $payments as $payment ) {
            $orderPayment               =   new OrderPayment;
            $orderPayment->order_id     =   $order->id;
            $orderPayment->namespace    =   $payment[ 'namespace' ];
            $orderPayment->value        =   $payment[ 'value' ];
            $orderPayment->author       =   Auth::id();
            $orderPayment->save();
        }
    }

    /**
     * Checks the order payements and compare
     * it to the product values and determine
     * if the order can proceed
     * @param Collection $products
     * @param array field
     */
    private function __checkOrderPayments( $products, $fields )
    {
        $totalPayments  =   0;
        $total          =   $products->map( function( $product ) {
            return $product[ 'product' ]->sale_price;
        })->sum();

        $allowedPaymentsGateways    =   config( 'NexoPOS.POS.payments' );

        foreach( $fields[ 'payments' ] as $payment ) {
            if ( in_array( $payment[ 'namespace' ], array_keys( $allowedPaymentsGateways ) ) ) {
                $totalPayments  =   $this->currencyService->define( $totalPayments )
                    ->additionateBy( $payment[ 'value' ] )
                    ->get();
            } else {
                throw new NotAllowedException([
                    'status'    =>  'failed',
                    'message'   =>  __( 'Unable to proceed. One of the submitted payment type is not supported.' )
                ]);
            }
        }

        /**
         * determine if according to the payment
         * we're free to proceed with that
         */
        if ( $totalPayments < $total ) {
            if ( 
                $this->options->get( 'nexopos.pos.allow_partials_orders', true ) === false && 
                $totalPayments > 0 
            ) {
                throw new NotAllowedException([
                    'status'    =>  'failed',
                    'message'   =>  __( 'Unable to proceed. Incomplete order aren\'t allowed. This option could be changed on the settings.' )
                ]);
            } else if ( 
                $this->options->get( 'nexopos.pos.allow_quotes_orders', true ) === false && 
                $totalPayments === 0 
            ) {
                throw new NotAllowedException([
                    'status'    =>  'failed',
                    'message'   =>  __( 'Unable to proceed. Quotes orders aren\'t allowed. This option could be changed on the settings.' )
                ]);
            }
        }

        if ( $totalPayments >= $total ) {
            $paymentStatus      =   'paid';
        } else if ( $totalPayments < $total && $totalPayments > 0 ) {
            $paymentStatus      =   'partially_paid';
        } else if ( $totalPayments === 0 ) {
            $paymentStatus      =   'unpaid';
        }

        return [
            'payments'          =>  $fields[ 'payments' ],
            'total'             =>  $total,
            'totalPayments'     =>  $totalPayments,
            'paymentStatus'     =>  $paymentStatus
        ];
    }

    private function __computeOrderTotal( $data )
    {
        /**
         * @param float $order 
         * @param float $total 
         * @param float $taxes
         * @param float $totalPayments
         * @param string $paymentStatus
         */
        extract( $data );

        $order->total           =   $total;
        $order->tax_value       =   $taxes;
        $order->payment_status  =   $paymentStatus;
        
        /**
         * compute change
         */
        $order->change          =   $this->currencyService->define( $totalPayments )
            ->subtractBy( $total )
            ->get();

        /**
         * compute gross total
         */
        $order->gross_total     =   $this->currencyService->define( $total )
            ->subtractBy( $taxes )
            ->get();

        $order->tendered        =   $totalPayments;

        return $order;
    }

    /**
     * @param Order order instance
     * @param array<OrderProduct> array of products
     * @return array[order,total,taxes]
     */
    private function __saveOrderProducts( $order, $products )
    {
        $total          =   0;
        $taxes          =   0;
        $gross          =   0;

        $products->each( function( $product ) use ( &$total, &$taxes, &$order, &$gross ) {
            /**
             * storing the product
             * history as a sale
             */
            $history                    =   [
                'order_id'      =>  $order->id,
                'unit_id'       =>  $product[ 'unit_id' ],
                'product_id'    =>  $product[ 'product' ]->id,
                'quantity'      =>  $product[ 'quantity' ],
                'unit_price'    =>  $product[ 'product' ]->sale_price,
                'total_price'   =>  $this->currencyService->define( $product[ 'product' ]->sale_price )
                    ->multiplyBy( $product[ 'quantity' ] )
                    ->get()
            ];
            
            $orderProduct                       =   new OrderProduct;
            $orderProduct->order_id             =   $order->id;
            $orderProduct->product_id           =   $product[ 'product' ]->id;
            $orderProduct->name                 =   $product[ 'product' ]->name;
            $orderProduct->quantity             =   $history[ 'quantity' ];

            /**
             * We might need to have another consideration
             * on how we do compute the taxes
             */
            if ( $product[ 'product' ][ 'tax_type' ] !== 'disabled' ) {
                $orderProduct->tax_id               =   $product[ 'product' ]->tax_id;
                $orderProduct->tax_type             =   $product[ 'product' ]->tax_type;
                $orderProduct->tax_value            =   $this->currencyService->define( $product[ 'product' ]->tax_value )
                    ->multiplyBy( $product[ 'quantity' ] )
                    ->get();
            }

            $orderProduct->sale_price           =   $product[ 'product' ]->sale_price;
            $orderProduct->net_price            =   $product[ 'product' ]->net_sale_price;
            $orderProduct->gross_price          =   $product[ 'product' ]->gross_sale_price;

            $orderProduct->total_gross_price    =   $this->currencyService->define( $product[ 'product' ]->gross_sale_price )
                ->multiplyBy( $product[ 'quantity' ] )
                ->get();
            $orderProduct->total_price          =   $this->currencyService->define( $product[ 'product' ]->sale_price )
                ->multiplyBy( $product[ 'quantity' ] )
                ->get();
            $orderProduct->total_net_price      =   $this->currencyService->define( $product[ 'product' ]->net_sale_price )
                ->multiplyBy( $product[ 'quantity' ] )
                ->get();

            $orderProduct->save();

            /**
             * @todo compute discounts
             */
            $total  =   $this->currencyService->define( $total )
                ->additionateBy( $orderProduct->total_price )
                ->get();

            $productTax     =   $this->currencyService->define( $product[ 'product' ]->tax_value )
                ->multiplyBy( $orderProduct[ 'quantity' ])
                ->get();

            $taxes  =   $this->currencyService->define( $taxes )
                ->additionateBy( $productTax )
                ->get();
            
            $this->productService->stockAdjustment( 'sold', $history );
        });

        return compact( 'total', 'taxes', 'order' );
    }

    /**
     * @param array of orderProduct
     */
    private function __checkProductStock( $items )
    {
        /**
         * helps to store similar items quantity
         * and compare it with available quantity
         */
        $fakeStorage                =   collect([]);

        $collectedUnits             =   new MapperService([]);

        /**
         * a product could be integrated 
         * many time on the cart. Each time we compute
         * the remaining value, we keep it as a reference 
         * without doing further call
         */
        $storedUnitForProduct       =   new MapperService([]);

        /**
         * product collection
         */
        $productCollection          =   new MapperService([]);

        /**
         * here comes a loop.
         * We'll been fetching from the database
         * we need somehow to integrate a cache
         * we'll also populate the unit for the item 
         * so that it can be reused 
         */
        $items  =   collect( $items )->map( function( array $orderProduct ) use ( 
            &$storedUnitForProduct, 
            &$collectedUnits, 
            &$productCollection, 
            &$fakeStorage ) {

            /**
             * Get the product if it's not yet 
             * defined
             */
            $product    =   $productCollection->retreive( $orderProduct[ 'product_id' ] ?? $orderProduct[ 'sku' ] )
                ->orReturn( function() use ( $orderProduct ) {
                    if ( ! empty( @$orderProduct[ 'product_id' ] ) ) {
                        return $this->productService->get( $orderProduct[ 'product_id' ] );
                    } else if ( ! empty( @$orderProduct[ 'sku' ] ) ) {
                        return $this->productService->getProductUsingSKUOrFail( $orderProduct[ 'sku' ] );
                    }
                }); 

            /**
             * What we're doing here
             * 1 - Get the unit assigned to the products being sold
             * 2 - check if the units assigned is what has been stored on the product 
             * 3 - If the a group is assigned to a product, the we check if that unit belongs to the unit group
             */
            try {

                /**
                 * doest the unit is already stored ?
                 * if yes, don't retreive from the DB
                 */
                $unit           =   $collectedUnits->retreive( $orderProduct[ 'unit_id' ] )
                    ->orReturn( function() use ( $orderProduct ) {
                        return $this->unitService->get( $orderProduct[ 'unit_id' ] );
                    });

                /**
                 * we need to check if the 
                 * unit provided on the order match the unit 
                 * saved either as a unit group or just a unit
                 */
                if ( $product->selling_unit_type === 'unit' && $product->selling_unit_id !== $orderProduct[ 'unit_id' ] ) {
                    throw new \Exception(
                        sprintf(
                            __( 'Unable to proceed. There is a mismatch between the unit provided and the unit assigned to the product %s' ),
                            $product->name
                        )
                    );
                } else if ( $product->selling_unit_type === 'unit-group' ) {
                    $group  =   $unit->group()->first();
                    if ( $group->id !== $product->selling_unit_id ) {
                        throw new \Exception(
                            sprintf(
                                __( 'Unable to proceed. The unit provided for the item "%s" doesn\'t belong to the unit group "%s"' ),
                                $product->name,
                                $group->name
                            )
                        );
                    }
                }

            } catch( NotFoundException $exception ) {
                throw new \Exception( 
                    sprintf( 
                        __( 'Unable to proceed, the product "%s" has a unit which is cannot be retreived. It might have been deleted.' ),
                        $product->name
                    )
                );
            }

            /**
             * have we ever fetched this unit quantity ?
             * if yes, then let's cache it. Specially if 
             * the item has already been saved.
             */
            $label              =   $unit->id . '-' . $product->id;
            $productQuantity    =   $storedUnitForProduct->retreive( $label )
                ->orReturn( function() use ( $orderProduct, $product ) {
                    $return     =   $this->productService->getUnitQuantity( $product->id, $orderProduct[ 'unit_id' ] );;
                    return $return;
                });   

            if ( ! $productQuantity instanceof ProductUnitQuantity ) {                
                throw new \Exception(
                    sprintf( 
                        __( 'The Product "%s" doens\'t have any stock available' ),
                        $product->name
                    )
                );
            }
            
            $isStoredOnFakeStorage  =   false;
            $fakeStorage->each( 
                function( &$product )
                use ( $productQuantity,  &$isStoredOnFakeStorage, $orderProduct ) {
                
                /**
                 * let's search to see if a similar product has
                 * been stored on the fake storage
                 */
                if ( $product->label === $productQuantity->product_id . '-' . $productQuantity->unit_id ) {
                    /**
                     * the product as been found
                     */
                    $isStoredOnFakeStorage = true;

                    $product->quantity_count    =  $this->currencyService->define( $product->quantity_count )
                        ->additionateBy( $orderProduct[ 'quantity' ] )
                        ->get();
                }
            });

            if ( ! $isStoredOnFakeStorage ) {
                $newProduct                     =   ( object ) $productQuantity->toArray();
                $newProduct->label              =   $productQuantity->product_id . '-' . $productQuantity->unit_id;
                $newProduct->quantity_count     =   $orderProduct[ 'quantity' ];
                $newProduct->product            =   $product;
                $newProduct->unit               =   $unit;
                $fakeStorage->push( $newProduct );
            }  
            
            $orderProduct[ 'unit' ]     =   $unit;
            $orderProduct[ 'product' ]  =   $product;
            
            return $orderProduct;
        });

        $fakeStorage->each( function( $storage ) {
            if ( $storage->quantity_count > $storage->quantity ) {
                throw new \Exception(
                    sprintf(
                        __( 'The stock of the product "%s" is not enough to save the order. "%s" quantity(ies) for the unit "%s" is remaining' ),
                        $storage->product->name,
                        $storage->quantity,
                        $storage->unit->name
                    )
                );
            }
        });

        return $items;
    }

    /**
     * @todo we need to be able to
     * change the code format
     */
    public function generateOrderCode()
    {
        $now        =   $this->dateService->now()->toDateString();
        $count      =   DB::table( 'nexopos_orders_count' )
            ->where( 'date', $now )
            ->value( 'count' );

        if ( $count === null ) {
            $count  =   1;
            DB::table( 'nexopos_orders_count' )
                ->insert([
                    'date'      =>  $now,
                    'count'     =>  $count
                ]);
        }

        DB::table( 'nexopos_orders_count' )
            ->where( 'date', $now )
            ->increment( 'count' );

        $carbon     =   $this->dateService->now();

        return $carbon->year . '-' . str_pad( $carbon->month, 2, STR_PAD_LEFT ) . '-' . str_pad( $carbon->day, 2, STR_PAD_LEFT ) . '-' . str_pad( $count, 3, 0, STR_PAD_LEFT );
    }

    private function __initOrder( $fields )
    {
        /**
         * let's save the order at 
         * his initial state
         */
        $order                      =   new Order;
        $order->customer_id         =   $fields[ 'customer_id' ];
        $order->total               =   0;
        $order->gross_total         =   0;
        $order->discount            =   0;
        $order->type                =   'in_store';
        $order->payment_status      =   'unpaid';
        $order->delivery_status     =   'pending';
        $order->process_status      =   'pending';
        $order->author              =   Auth::id();
        $order->tax_value           =   0;
        $order->code                =   $this->generateOrderCode();
        $order->save();

        return $order;
    }

    private function __customerIsDefined( $fields )
    {
        try {
            return $this->customerService->get( $fields[ 'customer_id' ] );
        } catch( NotFoundException $exception ) {
            throw new NotFoundException([
                'status'    =>  'failed',
                'message'   =>  __( 'Unable to find the customer using the provided ID. The order creation has failed.')
            ]);
        }
    }

    /**
     * Refund a product from an order
     * @param int product id
     * @param string status : sold, returned, defective
     */
    public function refundSingleProduct( Order $order, OrderProduct $product, $status )
    {
        if ( ! in_array( $status, [ 'sold', 'returned', 'damaged' ] ) ) {
            throw new NotAllowedException([
                'status'    =>  'failed',
                'message'   =>  __( 'unable to proceed to a refund as the provided status is not supported' )
            ]);
        }

        if ( $order->unpaid ) {
            throw new NotAllowedException([
                'status'    =>  'failed',
                'message'   =>  __( 'Unable to proceed a refund on an unpaid order.' )
            ]);
        }

        /**
         * we should broadcast an event
         * while doing this
         */
        $product->status        =   $status;
        $product->author        =   Auth::id(); 
        $product->save();

        event( new OrderAfterProductRefundedEvent( $order, $product ) );

        /**
         * we do proceed by doing an initial return
         */
        $this->productService->stockAdjustment( 'returned', [
            'total_price'       =>  $product->total_price,
            'quantity'          =>  $product->quantity,
            'unit_price'        =>  $product->sale_price
        ]);

        /**
         * If the returned stock is damaged
         * then we can pull this out from the stock
         */
        if ( $status === 'damaged' ) {
            $this->productService->stockAdjustment( $status, [
                'total_price'       =>  $product->total_price,
                'quantity'          =>  $product->quantity,
                'unit_price'        =>  $product->sale_price
            ]);
        }

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The product %s has been successfully refunded.' ),
            'data'      =>  compact( 'product' )
        ];
    }

    /**
     * Return a single order product
     * @param int product id
     * @return OrderProduct
     */
    public function getOrderProduct( $product_id )
    {
        $product    =   OrderProduct::find( $product_id );
        
        if ( ! $product instanceof OrderProduct ) {
            throw new NotFoundException([
                'status'    =>  'failed',
                'message'   =>  __( 'Unable to find the order product using the provided id.' )
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
    public function getOrderProducts( $identifier, $pivot = 'id' )
    {
        return $this->getOrder( $identifier, $pivot )->products;
    }

    /**
     * return a specific 
     * order using a provided identifier and pivot
     * @param mixed identifier
     * @param string pivot
     * @return Order
     */
    public function getOrder( $identifier, $as = 'id' )
    {
        if ( in_array( $as, [ 'id', 'code' ] ) ) {
            $order  =   Order::where( $as, $identifier )
                ->first();
            
            if ( ! $order instanceof Order ) {
                throw new NotFoundException([
                    'status'    =>  'failed',
                    'message'   =>  sprintf(
                        __( 'Unable to find the requested order using "%s" as pivot and "%s" as identifier' ),
                        $as,
                        $identifier
                    )
                ]);
            }

            $order->products;

            return $order;
        }

        throw new NotAllowedException([
            'status'    =>  'failed',
            'message'   =>  __( 'Unable to fetch the order as the provided pivot argument is not supported.' )
        ]);
    }

    /**
     * Get all the order that has been
     * already created
     * @param void
     * @return array of orders
     */
    public function getOrders( $filter = 'mixed' )
    {
        if ( in_array( $filter, [ 'paid', 'unpaid', 'refunded' ] ) ) {
            return Order::where( 'payment_status', $filter )
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
    public function addProducts( Order $order, $products )
    {
        $products   =   $this->__checkProductStock( $products );

        /**
         * let's save the products
         * to the order now as the stock
         * seems to be okay
         */
        $this->__saveOrderProducts( $order, $products );

        /**
         * Now we should refresh the order
         * to have the total computed
         */
        $this->refreshOrder( $order );

        return [
            'status'    =>  'success',
            'message'   =>  sprintf(
                __( 'The product has been added to the order "%s"'),
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
     */
    public function refreshOrder( Order $order )
    {
        $products               =   $this->getOrderProducts( $order->id );
        
        $productTotal           =   $products->map( function( $product ) {
            return floatval( $product->total_price );
        })->reduce( function( $before, $after ) {
            return $before + $after;
        });
        
        $productGrossTotal      =   $products->map( function( $product ) {
            return floatval( $product->total_gross_price );
        })->reduce( function( $before, $after ) {
            return $before + $after;
        });

        $productsTotalTaxes     =   $products->map( function( $product ) {
            return floatval( $product->tax_value );
        })->reduce( function( $before, $after ) {
            return $before + $after;
        });

        $orderShipping          =   $order->shipping;

        /**
         * We're not proceeding to 
         * the computing. 
         * @todo we might need to dispatch an event
         */
        $order->gross_total     =   $productGrossTotal;
        $order->total           =   $productTotal;
        $order->tax_value       =   $productsTotalTaxes;
        $order->save();

        return [
            'status'    =>  'success',
            'message'   =>  __( 'the order has been succesfully computed.' ),
            'data'      =>  compact( 'order' )
        ];
    }

    /**
     * Delete a specific order
     * and make product adjustment
     * @param Order order
     * @return array response
     */
    public function deleteOrder( Order $order )
    {
        event( new OrderBeforeDeleteEvent( $order ) );

        $products       =   $this->getOrderProducts( $order->id );
        
        $products->map( function( $product ) {
            if ( $product->status === 'sold' ) {
                /**
                 * we do proceed by doing an initial return
                 */
                $this->productService->stockAdjustment( 'returned', [
                    'total_price'       =>  $product->total_price,
                    'quantity'          =>  $product->quantity,
                    'unit_price'        =>  $product->sale_price
                ]);
            }

            $product->delete();
        });
        
        $order->delete();

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The order has been deleted' )
        ];
    }

    /**
     * Delete a product that is included 
     * within a specific order and refresh the order
     * @param Order order instance
     * @param int product id
     * @return array response
     */
    public function deleteOrderProduct( Order $order, $product_id )
    {
        $hasDeleted     =   false;

        $order->products->map( function( $product ) use ( $product_id, &$hasDeleted ) {
            if ( $product->id === intval( $product_id ) ) {

                event( new OrderBeforeDeleteProductEvent( $product ) );

                $product->delete();
                $hasDeleted     =   true;
            }
        });

        if ( $hasDeleted ) {
            $this->refreshOrder( $order );
    
            return [
                'status'    =>  'success',
                'message'   =>  __( 'The product has been successfully deleted from the order.' )
            ];
        }

        throw new NotFoundException([
            'status'    =>  'failed',
            'message'   =>  __( 'Unable to find the requested product on the provider order.' )
        ]);
    }

    /**
     * get orders payments
     * @param int order id
     * @return array of payments
     */
    public function getOrderPayments( $orderID )
    {
        $order  =   $this->getOrder( $orderID );
        return $order->payments;
    }
}
