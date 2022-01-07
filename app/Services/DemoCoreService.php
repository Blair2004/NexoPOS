<?php
namespace App\Services;

use App\Models\AccountType;
use App\Models\Customer;
use App\Models\CustomerCoupon;
use App\Models\ExpenseCategory;
use App\Models\Procurement;
use App\Models\Product;
use App\Models\Provider;
use App\Models\Tax;
use App\Models\TaxGroup;
use App\Models\Unit;
use App\Models\UnitGroup;
use Database\Seeders\CustomerGroupSeeder;
use Database\Seeders\DefaultProviderSeeder;
use Faker\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DemoCoreService
{
    protected $categoryService;
    protected $productService;
    protected $orderCount           =   25;
    protected $customOrderParams    =   [];
    protected $customDate           =   true;
    protected $customProductParams  =   []; 
    protected $shouldMakePayment    =   true;

    /**
     * @var OrdersService
     */
    protected $orderService;

    /**
     * @var ProcurementService
     */
    protected $procurementService;
    protected $user;

    /**
     * Will configure a basic unit system
     * @param void
     * @return void
     */
    public function prepareDefaultUnitSystem()
    {
        $group  =   UnitGroup::where( 'name', __( 'Countable' ) )->first();

        if ( ! $group instanceof UnitGroup ) {
            $group          =   new UnitGroup;
            $group->name    =   __( 'Countable' );
            $group->author  =   Auth::id();
            $group->save();
        }
        
        $unit       =   Unit::identifier( 'piece' )->first();

        if ( ! $unit instanceof Unit ) {
            $unit               =   new Unit;
            $unit->name         =   __( 'Piece' );
            $unit->identifier   =   'piece';
            $unit->description  =   '';
            $unit->author       =   Auth::id();
            $unit->group_id     =   $group->id;
            $unit->base_unit    =   true;
            $unit->value        =   1;
            $unit->save();
        }
    }

    public function createAccountingAccounts()
    {
        /**
         * @var ExpenseService $expenseService
         */
        $expenseService     =   app()->make( ExpenseService::class );
        
        $expenseService->createAccount([
            'name'      =>  __( 'Sales Account' ),
            'operation' =>  'credit',
            'account'   =>  '001'
        ]);

        ns()->option->set( 'ns_sales_cashflow_account', AccountType::account( '001' )->first()->id );

        $expenseService->createAccount([
            'name'  =>  __( 'Procurements Account' ),
            'operation' =>  'debit',
            'account'   =>  '002'
        ]);

        ns()->option->set( 'ns_procurement_cashflow_account', AccountType::account( '002' )->first()->id );

        $expenseService->createAccount([
            'name'  =>  __( 'Sale Refunds Account' ),
            'operation' =>  'debit',
            'account'   =>  '003'
        ]);

        ns()->option->set( 'ns_sales_refunds_account', AccountType::account( '003' )->first()->id );

        $expenseService->createAccount([
            'name'  =>  __( 'Spoiled Goods Account' ),
            'operation' =>  'debit',
            'account'   =>  '006'
        ]);

        ns()->option->set( 'ns_stock_return_spoiled_account', AccountType::account( '006' )->first()->id );

        $expenseService->createAccount([
            'name'  =>  __( 'Customer Crediting Account' ),
            'operation' =>  'credit',
            'account'   =>  '007'
        ]);

        ns()->option->set( 'ns_customer_crediting_cashflow_account', AccountType::account( '007' )->first()->id );

        $expenseService->createAccount([
            'name'  =>  __( 'Customer Debiting Account' ),
            'operation' =>  'credit',
            'account'   =>  '008'
        ]);

        ns()->option->set( 'ns_customer_debitting_cashflow_account', AccountType::account( '007' )->first()->id );
    }

    public function createCustomers()
    {
        (new CustomerGroupSeeder)->run();
    }

    public function createProviders()
    {
        (new DefaultProviderSeeder)->run();
    }   
    
    /**
     * Create sample tax group
     * and taxes on the system
     * @return void
     */
    public function createTaxes()
    {
        $taxGroup           =   TaxGroup::where( 'name', __( 'GST' ) )->first();

        if ( ! $taxGroup instanceof TaxGroup ) {
            $taxGroup           =   new TaxGroup;
            $taxGroup->name     =   __( 'GST' );
            $taxGroup->author   =   Auth::id();
            $taxGroup->save();
        }

        $tax            =   Tax::where( 'name', __( 'SGST' ) )->first();

        if ( ! $tax instanceof Tax ) {
            $tax            =   new Tax;
            $tax->name      =   __( 'SGST' );
            $tax->rate      =   8;
            $tax->tax_group_id  =   $taxGroup->id;
            $tax->author    =   Auth::id();
            $tax->save();
        }

        $tax            =   Tax::where( 'name', __( 'CGST' ) )->first();

        if ( ! $tax instanceof Tax ) {
            $tax            =   new Tax;
            $tax->name      =   __( 'CGST' );
            $tax->rate      =   8;
            $tax->tax_group_id  =   $taxGroup->id;
            $tax->author    =   Auth::id();
            $tax->save();
        }
    }

    public function performProcurement()
    {
        $faker          =   Factory::create();
        
        /**
         * @var TaxService
         */
        $taxService     =   app()->make( TaxService::class );

        /**
         * @var CurrencyService
         */
        $currencyService     =   app()->make( CurrencyService::class );

        $taxType        =   Arr::random([ 'inclusive', 'exclusive' ]);
        $taxGroup       =   TaxGroup::get()->random();
        $margin         =   25;

        $this->procurementService->create([
            'name'                  =>  sprintf( __( 'Sample Procurement %s' ), Str::random(5) ),
            'general'   =>  [
                'provider_id'           =>  Provider::get()->random()->id,
                'payment_status'        =>  Procurement::PAYMENT_PAID,
                'delivery_status'       =>  Procurement::DELIVERED,
                'automatic_approval'    =>  1
            ], 
            'products'  =>  Product::withStockEnabled()
                ->with( 'unitGroup' )
                ->get()
                ->map( function( $product ) {
                return $product->unitGroup->units->map( function( $unit ) use ( $product ) {
                    $unitQuantity       =   $product->unit_quantities->filter( fn( $q ) => ( int ) $q->unit_id === ( int ) $unit->id )->first();

                    return ( object ) [
                        'unit'      =>  $unit,
                        'unitQuantity'  =>  $unitQuantity,
                        'product'   =>  $product
                    ];
                });
            })->flatten()->map( function( $data ) use ( $taxService, $taxType, $taxGroup, $margin, $faker ) {

                return [
                    'product_id'            =>  $data->product->id,
                    'gross_purchase_price'  =>  15,
                    'net_purchase_price'    =>  16,
                    'purchase_price'        =>  $taxService->getTaxGroupComputedValue( 
                        $taxType, 
                        $taxGroup, 
                        $data->unitQuantity->sale_price - $taxService->getPercentageOf(
                            $data->unitQuantity->sale_price,
                            $margin
                        )
                    ),
                    'quantity'              =>  $faker->numberBetween(500,1000),
                    'tax_group_id'          =>  $taxGroup->id,
                    'tax_type'              =>  $taxType,
                    'tax_value'             =>  $taxService->getTaxGroupVatValue( 
                        $taxType, 
                        $taxGroup, 
                        $data->unitQuantity->sale_price - $taxService->getPercentageOf(
                            $data->unitQuantity->sale_price,
                            $margin
                        ) 
                    ),
                    'total_purchase_price'  =>  $taxService->getTaxGroupComputedValue( 
                        $taxType, 
                        $taxGroup, 
                        $data->unitQuantity->sale_price - $taxService->getPercentageOf(
                            $data->unitQuantity->sale_price,
                            $margin
                        ) 
                    ) * 250,
                    'unit_id'               =>  $data->unit->id,
                ];
            })
        ]);
    }

    public function createSales()
    {
        /**
         * @var ReportService $reportService
         */
        $reportService      =   app()->make( ReportService::class );
        $dates              =   [];
        $startOfRange       =   ns()->date->clone()->subDays(7);

        for( $i = 0; $i <= 7; $i++ ) {
            $dates[]    =   $startOfRange->clone();
            $startOfRange->addDay();
        }

        for( $i = 0; $i < $this->orderCount; $i++ ) {

            $currentDate    =   Arr::random( $dates );

            /**
             * @var CurrencyService
             */
            $currency       =   app()->make( CurrencyService::class );
            $faker          =   Factory::create();
            $products       =   Product::with( 'unit_quantities' )->get()->shuffle()->take(3);
            $shippingFees   =   $faker->randomElement([10,15,20,25,30,35,40]);
            $discountRate   =   $faker->numberBetween(0,5);

            $products           =   $products->map( function( $product ) use ( $faker ) {
                $unitElement    =   $faker->randomElement( $product->unit_quantities );
                return array_merge([
                    'product_id'            =>  $product->id,
                    'quantity'              =>  $faker->numberBetween(1,10),
                    'unit_price'            =>  $unitElement->sale_price,
                    'unit_quantity_id'      =>  $unitElement->id,
                ], $this->customProductParams );
            });

            /**
             * testing customer balance
             */
            $customer                   =   Customer::get()->random();
            $customerFirstPurchases     =   $customer->purchases_amount;
            $customerFirstOwed          =   $customer->owed_amount;

            $subtotal   =   ns()->currency->getRaw( $products->map( function( $product ) use ($currency) {
                return $currency
                    ->define( $product[ 'unit_price' ] )
                    ->multiplyBy( $product[ 'quantity' ] )
                    ->getRaw();
            })->sum() );

            $customerCoupon     =   CustomerCoupon::get()->last();

            if ( $customerCoupon instanceof CustomerCoupon ) {
                $allCoupons         =   [
                    [
                        'customer_coupon_id'    =>  $customerCoupon->id,
                        'coupon_id'             =>  $customerCoupon->coupon_id,
                        'name'                  =>  $customerCoupon->name,
                        'type'                  =>  'percentage_discount',
                        'code'                  =>  $customerCoupon->code,
                        'limit_usage'           =>  $customerCoupon->coupon->limit_usage,
                        'value'                 =>  $currency->define( $customerCoupon->coupon->discount_value )
                            ->multiplyBy( $subtotal )
                            ->divideBy( 100 )
                            ->getRaw(),
                        'discount_value'        =>  $customerCoupon->coupon->discount_value,
                        'minimum_cart_value'    =>  $customerCoupon->coupon->minimum_cart_value,
                        'maximum_cart_value'    =>  $customerCoupon->coupon->maximum_cart_value,
                    ]
                ];
    
                $totalCoupons   =   collect( $allCoupons )->map( fn( $coupon ) => $coupon[ 'value' ] )->sum();
            } else {
                $allCoupons             =   [];
                $totalCoupons           =   0;
            }

            $discountValue  =   $currency->define( $discountRate )
                ->multiplyBy( $subtotal )
                ->divideBy( 100 )
                ->getRaw();

            $discountCoupons    =   $currency->define( $discountValue )
                ->additionateBy( $allCoupons[0][ 'value' ] ?? 0 )
                ->getRaw();

            $dateString         =   $currentDate->startOfDay()->addHours( 
                $faker->numberBetween( 0,23 ) 
            )->format( 'Y-m-d H:m:s' );

            $orderData  =   array_merge([
                'customer_id'           =>  $customer->id,
                'type'                  =>  [ 'identifier' => 'takeaway' ],
                'discount_type'         =>  'percentage',
                'created_at'            =>  $this->customDate ? $dateString : null,
                'discount_percentage'   =>  $discountRate,
                'addresses'             =>  [
                    'shipping'          =>  [
                        'name'          =>  'First Name Delivery',
                        'surname'       =>  'Surname',
                        'country'       =>  'Cameroon',
                    ],
                    'billing'          =>  [
                        'name'          =>  'EBENE Voundi',
                        'surname'       =>  'Antony Hervé',
                        'country'       =>  'United State Seattle',
                    ]
                ],
                'coupons'               =>  $allCoupons,
                'subtotal'              =>  $subtotal,
                'shipping'              =>  $shippingFees,
                'products'              =>  $products->toArray(),
                'payments'              =>  $this->shouldMakePayment ? [
                    [
                        'identifier'    =>  'cash-payment',
                        'value'         =>  $currency->define( $subtotal )
                            ->additionateBy( $shippingFees )
                            ->subtractBy( 
                                $discountCoupons
                            ) 
                            ->getRaw()
                    ]
                ] : []
            ], $this->customOrderParams );

            $result     =   $this->orderService->create( $orderData );
        }
    }
}