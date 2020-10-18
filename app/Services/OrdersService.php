<?php

namespace App\Services;

use App\Events\OrderAfterCreatedEvent;
use App\Models\Order;
use App\Services\Options;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use App\Services\DateService;
use App\Models\OrderAddress;
use App\Models\OrderPayment;
use App\Models\OrderProduct;
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
use App\Models\DashboardDay;
use App\Models\OrderStorage;
use App\Models\ProductHistory;
use Illuminate\Support\Str;

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

    public function create($fields)
    {
        $customer   =   $this->__customerIsDefined($fields);

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
         *      'tax_group_id'      =>  // the tax that applies to the item
         *      'tax_value'         =>  // the total price of the tax
         *      'net_unit_price'    =>  // unit price with taxe over
         *      'gross_unit_price'  =>  // unit price without taxe over
         *      'net_total_price'   =>  // total unit price with taxe over
         *      'gross_total_price' =>  // total unit price without taxe over
         * }
         */
        $session_identifier                 =   Str::random( '10' );

        $fields[ 'products' ]      =   $this->__checkProductStock( $fields['products'], $session_identifier );

        /**
         * check discount validity and throw an
         * error is something is not set correctly.
         */
        $this->__checkDiscountVality( $fields );

        /**
         * determine the value of the product 
         * on the cart and compare it along with the payment made. This will
         * help to prevent partial or quote orders
         * @param float $total
         * @param float $totalPayments
         * @param array $payments
         * @param string $paymentStatus
         */
        extract($this->__checkOrderPayments( $fields ) );

        /**
         * check delivery informations before
         * proceeding
         */
        $this->__checkAddressesInformations( $fields );

        /**
         * ------------------------------------------
         *                  WARNING
         * ------------------------------------------
         * all what follow will proceed database 
         * modification. All verification on current order
         * should be made prior this section
         */
        $order      =   $this->__initOrder($fields);

        $this->__saveAddressInformations($order, $fields);
        $this->__saveOrderPayments($order, $payments);

        /**
         * @var Order $order
         * @var float $taxes
         * @var float $subTotal
         */
        extract($this->__saveOrderProducts( $order, $fields[ 'products' ] ) );

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
        event( new OrderAfterCreatedEvent( $order ) );

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The order has been placed.' ),
            'data'      =>  compact( 'order' )
        ];
    }

    private function __saveOrderDiscount($order, $fields)
    {
        if (in_array($fields['discount_type'], ['flat', 'percentage'])) {
            $order->discount_type   =   $fields['discount_type'];
        }

        switch ($fields['discount_type']) {
            case 'flat':
                $order->discount        =   $this->currencyService->define($fields['discount'])->get();
                $order->total           =   $this->currencyService->define($order->subtotal)
                    ->subtractBy($order->discount)
                    ->get();
                $order->gross_total     =   $this->currencyService->define($order->subtotal)
                    ->subtractBy($order->discount)
                    ->get();
                break;
            case 'percentage':
                $discountValue      =   $this->currencyService->define($order->subtotal)
                    ->multipliedBy( $fields['discount_percentage'] )
                    ->dividedBy(100)
                    ->get();
                $order->discount    =   $discountValue;
                $order->total       =   $this->currencyService->define($order->subtotal)
                    ->subtractBy($discountValue)
                    ->get();
                $order->gross_total =   $this->currencyService->define($order->subtotal)
                    ->subtractBy($discountValue)
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
    private function __getShippingFee($fields)
    {
        return $fields['shipping'] ?? 0;
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
                    return floatval($product['quantity']) * floatval($product['sale_price']);
                })->reduce(function ($before, $after) {
                    return $before + $after;
                });

                $shippingFees       =   $this->__getShippingFee($fields);

                if ($fields['discount'] > $productsTotal + $shippingFees) {
                    throw new NotAllowedException([
                        'status'    =>  'failed',
                        'message'   =>  __('A discount cannot exceed the total value of an order.')
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

        if ( ! empty( $fields[ 'addresses' ] ) ) {
            foreach (['shipping', 'billing'] as $type) {
                $keys   =   array_keys($fields['addresses'][$type]);
                foreach ($keys as $key) {
                    if (!in_array($key, $allowedKeys)) {
                        throw new NotAllowedException(sprintf(__('Unable to proceed because the "%s" field is an unsupported attribute.'), $key));
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
            $orderShipping          =   new OrderAddress;
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

    private function __saveOrderPayments($order, $payments)
    {
        foreach ($payments as $payment) {
            $orderPayment               =   new OrderPayment;
            $orderPayment->order_id     =   $order->id;
            $orderPayment->identifier   =   $payment['identifier'];
            $orderPayment->value        =   $payment['amount'];
            $orderPayment->author       =   Auth::id();
            $orderPayment->save();
        }

        $order->tendered    =   collect( $payments )->map( fn( $payment ) => floatval( $payment[ 'amount' ] ) )->sum();
    }

    /**
     * Checks the order payements and compare
     * it to the product values and determine
     * if the order can proceed
     * @param Collection $products
     * @param array field
     */
    private function __checkOrderPayments( $fields)
    {
        $totalPayments  =   0;

        $total          =   $fields[ 'products' ]->map(function ($product) {
            return floatval($product['total_price']);
        })->sum() + $this->__getShippingFee($fields);

        $allowedPaymentsGateways    =   config('nexopos.pos.payments');

        foreach ( $fields['payments'] as $payment) {
            if (in_array($payment['identifier'], array_keys($allowedPaymentsGateways))) {
                $totalPayments  =   $this->currencyService->define($totalPayments)
                    ->additionateBy($payment['amount'])
                    ->get();
            } else {
                throw new NotAllowedException([
                    'status'    =>  'failed',
                    'message'   =>  __('Unable to proceed. One of the submitted payment type is not supported.')
                ]);
            }
        }

        /**
         * determine if according to the payment
         * we're free to proceed with that
         */
        if ($totalPayments < $total) {
            if (
                $this->optionsService->get('nexopos.pos.allow_partials_orders', true) === false &&
                $totalPayments > 0
            ) {
                throw new NotAllowedException([
                    'status'    =>  'failed',
                    'message'   =>  __('Unable to proceed. Incomplete order aren\'t allowed. This option could be changed on the settings.')
                ]);
            } else if (
                $this->optionsService->get('nexopos.pos.allow_quotes_orders', true) === false &&
                $totalPayments === 0
            ) {
                throw new NotAllowedException([
                    'status'    =>  'failed',
                    'message'   =>  __('Unable to proceed. Quotes orders aren\'t allowed. This option could be changed on the settings.')
                ]);
            }
        }

        if ($totalPayments >= $total) {
            $paymentStatus      =   'paid';
        } else if ($totalPayments < $total && $totalPayments > 0) {
            $paymentStatus      =   'partially_paid';
        } else if ($totalPayments === 0) {
            $paymentStatus      =   'unpaid';
        }

        return [
            'payments'          =>  $fields['payments'],
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
            ->subtractBy( $order->discount )
            ->get();

        $order->tax_value       =   $taxes;
        $order->payment_status  =   $paymentStatus;

        /**
         * compute change
         */
        $order->change          =   $this->currencyService->define( $order->tendered )
            ->subtractBy( $order->total )
            ->get();

        /**
         * compute gross total
         */
        $order->gross_total     =   $this->currencyService->define( $order->subtotal )
            ->subtractBy($taxes)
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
        $subTotal          =   0;
        $taxes          =   0;
        $gross          =   0;

        $products->each(function ($product) use (&$subTotal, &$taxes, &$order, &$gross) {
            /**
             * storing the product
             * history as a sale
             */
            $history                    =   [
                'order_id'      =>  $order->id,
                'unit_id'       =>  $product['unit_id'],
                'product_id'    =>  $product['product']->id,
                'quantity'      =>  $product['quantity'],
                'unit_price'    =>  $product['unit_price'],
                'total_price'   =>  $this->currencyService->define($product['unit_price'])
                    ->multiplyBy($product['quantity'])
                    ->get()
            ];

            $orderProduct                               =   new OrderProduct;
            $orderProduct->order_id                     =   $order->id;
            $orderProduct->unit_quantity_id             =   $product[ 'unit_quantity_id' ]; 
            $orderProduct->unit_id                      =   $product[ 'unit_id' ];
            $orderProduct->product_id                   =   $product[ 'product' ]->id;
            $orderProduct->name                         =   $product[ 'product' ]->name;
            $orderProduct->quantity                     =   $history[ 'quantity'];

            /**
             * We might need to have another consideration
             * on how we do compute the taxes
             */
            if ( $product['product']['tax_type'] !== 'disabled' && ! empty( $product['product']->tax_group_id )) {
                $orderProduct->tax_group_id         =   $product['product']->tax_group_id;
                $orderProduct->tax_type             =   $product['product']->tax_type;
                $orderProduct->tax_value            =   $product[ 'tax_value' ];
            }

            $orderProduct->unit_price           =   $product['unit_price'];
            $orderProduct->net_price            =   $product['unitQuantity']->incl_tax_sale_price;
            $orderProduct->gross_price          =   $product['unitQuantity']->excl_tax_sale_price;
            $orderProduct->discount_type        =   $product[ 'discount_type' ] ?? 'none';
            $orderProduct->discount             =   $product[ 'discount' ] ?? 0;
            $orderProduct->discount_percentage  =   $product[ 'discount_percentage' ] ?? 0;

            $orderProduct->total_gross_price    =   $this->currencyService->define($product['unitQuantity']->excl_tax_sale_price)
                ->multiplyBy($product['quantity'])
                ->get();
            $orderProduct->total_price          =   $this->currencyService->define($product['unit_price'])
                ->multiplyBy($product['quantity'])
                ->get();
            $orderProduct->total_net_price      =   $this->currencyService->define($product['unitQuantity']->incl_tax_sale_price)
                ->multiplyBy($product['quantity'])
                ->get();

            $orderProduct->save();

            /**
             * @todo compute discounts
             */
            $subTotal  =   $this->currencyService->define($subTotal)
                ->additionateBy($orderProduct->total_price)
                ->get();

            $taxes  =   $this->currencyService->define($taxes)
                ->additionateBy($product[ 'tax_value' ])
                ->get();

            $this->productService->stockAdjustment( ProductHistory::ACTION_SOLD, $history);
        });

        return compact('subTotal', 'taxes', 'order');
    }

    /**
     * @param array of orderProduct
     */
    private function __checkProductStock( $items, $session_identifier )
    {
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
        $items  =   collect($items)->map(function (array $orderProduct) use (
            $productCollection,
            $session_identifier
        ) {
            /**
             * Get the product if it's not yet 
             * defined
             * @todo use proper cache here
             */
            $product    =   $productCollection->retreive($orderProduct['product_id'] ?? $orderProduct['sku'])
                ->orReturn(function () use ($orderProduct) {
                    if (!empty(@$orderProduct['product_id'])) {
                        return $this->productService->get($orderProduct['product_id']);
                    } else if (!empty(@$orderProduct['sku'])) {
                        return $this->productService->getProductUsingSKUOrFail($orderProduct['sku']);
                    }
                });
            
            /**
             * This will calculate the product default field
             * when they aren't provided. 
             */
            $orderProduct           =   $this->computeProduct( $orderProduct, $product );
            $productUnitQuantity    =   ProductUnitQuantity::findOrFail( $orderProduct[ 'unit_quantity_id' ] );

            if ($product->stock_management === Product::STOCK_MANAGEMENT_ENABLED) {

                /**
                 * What we're doing here
                 * 1 - Get the unit assigned to the products being sold
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
                                __( 'Unable to proceed there is not enough stock for %s. Using the unit %s' ),
                                $product->name,
                                $productUnitQuantity->unit->name
                            )
                        );
                    }

                    /**
                     * We keep reference on the database
                     * that's more easier.
                     */
                    $storage                                =   new OrderStorage;
                    $storage->product_id                    =   $product->id;
                    $storage->product_unit_id               =   $productUnitQuantity->unit->id;
                    $storage->unit_quantity_id              =   $orderProduct[ 'unit_quantity_id' ];
                    $storage->quantity                      =   $orderProduct[ 'quantity' ];
                    $storage->session_identifier            =   $session_identifier;
                    $storage->save();

                } catch (NotFoundException $exception) {
                    throw new \Exception(
                        sprintf(
                            __('Unable to proceed, the product "%s" has a unit which is cannot be retreived. It might have been deleted.'),
                            $product->name
                        )
                    );
                }
            }

            $orderProduct[ 'unit_id' ]              =   $productUnitQuantity->unit->id;
            $orderProduct[ 'unit_quantity_id' ]     =   $productUnitQuantity->id;
            $orderProduct[ 'total_price' ]          =   $orderProduct[ 'total_price' ];
            $orderProduct[ 'product' ]              =   $product;
            $orderProduct[ 'unitQuantity' ]         =   $productUnitQuantity;

            return $orderProduct;
        });

        /**
         * @todo delete storage session
         */

        return $items;
    }

    public function computeProduct( $fields, Product $product )
    {
        $sale_price     =   ( $fields[ 'sale_price' ] ?? $product->sale_price );
        
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
            $fields[ 'tax_value' ]      =   $this->taxService->getComputedTaxGroupValue(
                $product->tax_type,
                $product->tax_group_id,
                $sale_price
            ) * floatval( $fields[ 'quantity' ] );        
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

    private function __initOrder($fields)
    {
        /**
         * let's save the order at 
         * his initial state
         */
        $order                      =   new Order;
        $order->customer_id         =   $fields['customer_id'];
        $order->shipping            =   $fields[ 'shipping' ] ?? 0; // if shipping is not provided, we assume it's free
        $order->subtotal            =   $fields[ 'subtotal' ] ?? $this->computeSubTotal( $fields, $order );
        $order->discount_type       =   $fields['discount_type'] ?? null;
        $order->discount_percentage =   $fields['discount_percentage'] ?? 0;
        $order->discount            =   $fields['discount'] ?? $this->computeDiscount( $fields, $order );
        $order->total               =   $fields[ 'total' ] ?? $this->computeTotal( $fields, $order );
        $order->type                =   $fields['type']['identifier'];
        $order->payment_status      =   'unpaid';
        $order->delivery_status     =   'pending';
        $order->process_status      =   'pending';
        $order->author              =   Auth::id();
        $order->tax_value           =   $fields[ 'tax_value' ] ?? $this->computeOrderTaxValue( $fields, $order );
        $order->code                =   $this->generateOrderCode();
        $order->save();

        return $order;
    }

    /**
     * Compute the discount data
     * @param array $fields
     * @return int $discount
     */
    public function computeDiscount( $fields, $order )
    {
        if ( ! empty( $fields[ 'discount_type' ] ) && ! empty( $fields[ 'discount_percentage' ] ) && $fields[ 'discount_type' ] === 'percentage' ) {
            return ( floatval( $fields[ 'subtotal' ] ?? $order->subtotal ) * floatval( $fields[ 'discount_percentage' ] ) ) / 100;
        } else {
            return $fields[ 'discount' ] ?? 0;
        }
    }

    public function computeOrderTaxValue( $fields, $order )
    {
        return $fields[ 'products' ]->map( fn( $product ) => $product[ 'tax_value' ] )->sum();
    }

    public function computeTotal( $fields, $order )
    {
        return ( $order->subtotal - $order->discount ) + $order->shipping;
    }

    public function computeSubTotal( $fields, $order )
    {
        return collect( $fields[ 'products' ] )
            ->map( fn( $product ) => floatval( $product[ 'total_price' ] ) )
            ->sum();
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

    /**
     * Refund a product from an order
     * @param int product id
     * @param string status : sold, returned, defective
     */
    public function refundSingleProduct(Order $order, OrderProduct $product, $status)
    {
        if (!in_array($status, ['sold', 'returned', 'damaged'])) {
            throw new NotAllowedException([
                'status'    =>  'failed',
                'message'   =>  __('unable to proceed to a refund as the provided status is not supported')
            ]);
        }

        if ($order->unpaid) {
            throw new NotAllowedException([
                'status'    =>  'failed',
                'message'   =>  __('Unable to proceed a refund on an unpaid order.')
            ]);
        }

        /**
         * we should broadcast an event
         * while doing this
         */
        $product->status        =   $status;
        $product->author        =   Auth::id();
        $product->save();

        event(new OrderAfterProductRefundedEvent($order, $product));

        /**
         * we do proceed by doing an initial return
         */
        $this->productService->stockAdjustment('returned', [
            'total_price'       =>  $product->total_price,
            'quantity'          =>  $product->quantity,
            'unit_price'        =>  $product->sale_price
        ]);

        /**
         * If the returned stock is damaged
         * then we can pull this out from the stock
         */
        if ($status === 'damaged') {
            $this->productService->stockAdjustment($status, [
                'total_price'       =>  $product->total_price,
                'quantity'          =>  $product->quantity,
                'unit_price'        =>  $product->sale_price
            ]);
        }

        return [
            'status'    =>  'success',
            'message'   =>  __('The product %s has been successfully refunded.'),
            'data'      =>  compact('product')
        ];
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
                ->with( 'products' )
                ->with( 'customer' )
                ->first();

            if (!$order instanceof Order) {
                throw new NotFoundException([
                    'status'    =>  'failed',
                    'message'   =>  sprintf(
                        __('Unable to find the requested order using "%s" as pivot and "%s" as identifier'),
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
     */
    public function refreshOrder(Order $order)
    {
        $products               =   $this->getOrderProducts($order->id);

        $productTotal           =   $products->map(function ($product) {
            return floatval($product->total_price);
        })->reduce(function ($before, $after) {
            return $before + $after;
        });

        $productGrossTotal      =   $products->map(function ($product) {
            return floatval($product->total_gross_price);
        })->reduce(function ($before, $after) {
            return $before + $after;
        });

        $productsTotalTaxes     =   $products->map(function ($product) {
            return floatval($product->tax_value);
        })->reduce(function ($before, $after) {
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
        event(new OrderBeforeDeleteEvent($order));

        $products       =   $this->getOrderProducts($order->id);

        $products->map(function ($product) {
            if ($product->status === 'sold') {
                /**
                 * we do proceed by doing an initial return
                 */
                $this->productService->stockAdjustment('returned', [
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
            'message'   =>  __('The order has been deleted')
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

        $order->products->map(function ($product) use ($product_id, &$hasDeleted) {
            if ($product->id === intval($product_id)) {

                event( new OrderBeforeDeleteProductEvent($product));

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
        switch( $type ) {
            case 'paid': return __( 'Paid' ); break;
            case 'unpaid': return __( 'Unpaid' ); break;
            case 'partially_paid': return __( 'Partially Paid' ); break;
            default : return sprintf( __( 'Unknown Status (%s)' ), $type ); break;
        }
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
}
