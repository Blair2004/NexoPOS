<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerCoupon;
use App\Models\Procurement;
use App\Models\Product;
use App\Models\Provider;
use App\Models\Register;
use App\Models\Role;
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

class DemoCoreService
{
    protected ProductCategoryService $categoryService;

    protected ProductService $productService;

    protected $orderCount = 14;

    protected $daysRange = 14;

    protected $customOrderParams = [];

    protected $customDate = true;

    protected $customProductParams = [];

    protected $shouldMakePayment = true;

    protected OrdersService $orderService;

    protected ProcurementService $procurementService;

    protected $user;

    /**
     * Will configure a basic unit system
     *
     * @param void
     * @return void
     */
    public function prepareDefaultUnitSystem()
    {
        $group = UnitGroup::where( 'name', __( 'Countable' ) )->first();

        if ( ! $group instanceof UnitGroup ) {
            $group = new UnitGroup;
            $group->name = __( 'Countable' );
            $group->author = Role::namespace( 'admin' )->users()->first()->id;
            $group->save();
        }

        $unit = Unit::identifier( 'piece' )->first();

        if ( ! $unit instanceof Unit ) {
            $unit = new Unit;
            $unit->name = __( 'Piece' );
            $unit->identifier = 'piece';
            $unit->description = '';
            $unit->author = Role::namespace( 'admin' )->users()->first()->id;
            $unit->group_id = $group->id;
            $unit->base_unit = true;
            $unit->value = 1;
            $unit->save();
        }

        $unit = Unit::identifier( 'small-box' )->first();

        if ( ! $unit instanceof Unit ) {
            $unit = new Unit;
            $unit->name = __( 'Small Box' );
            $unit->identifier = 'small-box';
            $unit->description = '';
            $unit->author = Auth::id();
            $unit->group_id = $group->id;
            $unit->base_unit = true;
            $unit->value = 6;
            $unit->save();
        }

        $unit = Unit::identifier( 'box' )->first();

        if ( ! $unit instanceof Unit ) {
            $unit = new Unit;
            $unit->name = __( 'Box' );
            $unit->identifier = 'box';
            $unit->description = '';
            $unit->author = Auth::id();
            $unit->group_id = $group->id;
            $unit->base_unit = true;
            $unit->value = 12;
            $unit->save();
        }
    }

    public function createBaseSettings()
    {
        $orderTypes = app()->make( OrdersService::class )->getTypeLabels();

        /**
         * @var Options $optionService
         */
        $optionService = app()->make( Options::class );

        $optionService->set(
            'ns_pos_order_types',
            array_keys( $orderTypes )
        );

        $optionService->set(
            'ns_currency_symbol',
            '$'
        );

        $optionService->set(
            'ns_currency_iso',
            'USD'
        );
    }

    public function createRegisters()
    {
        $register = new Register;
        $register->name = __( 'Terminal A' );
        $register->status = Register::STATUS_CLOSED;
        $register->author = ns()->getValidAuthor();
        $register->save();

        $register = new Register;
        $register->name = __( 'Terminal B' );
        $register->status = Register::STATUS_CLOSED;
        $register->author = ns()->getValidAuthor();
        $register->save();
    }

    public function createAccountingAccounts()
    {
        /**
         * @var TransactionService $service
         */
        $service = app()->make( TransactionService::class );
        $service->createDefaultAccounts();
    }

    public function createCustomers()
    {
        ( new CustomerGroupSeeder )->run();
    }

    public function createProviders()
    {
        ( new DefaultProviderSeeder )->run();
    }

    /**
     * Create sample tax group
     * and taxes on the system
     *
     * @return void
     */
    public function createTaxes()
    {
        $taxGroup = TaxGroup::where( 'name', __( 'GST' ) )->first();

        if ( ! $taxGroup instanceof TaxGroup ) {
            $taxGroup = new TaxGroup;
            $taxGroup->name = __( 'GST' );
            $taxGroup->author = Role::namespace( 'admin' )->users()->first()->id;
            $taxGroup->save();
        }

        $tax = Tax::where( 'name', __( 'SGST' ) )->first();

        if ( ! $tax instanceof Tax ) {
            $tax = new Tax;
            $tax->name = __( 'SGST' );
            $tax->rate = 8;
            $tax->tax_group_id = $taxGroup->id;
            $tax->author = Role::namespace( 'admin' )->users()->first()->id;
            $tax->save();
        }

        $tax = Tax::where( 'name', __( 'CGST' ) )->first();

        if ( ! $tax instanceof Tax ) {
            $tax = new Tax;
            $tax->name = __( 'CGST' );
            $tax->rate = 8;
            $tax->tax_group_id = $taxGroup->id;
            $tax->author = Role::namespace( 'admin' )->users()->first()->id;
            $tax->save();
        }
    }

    public function performProcurement()
    {
        $faker = Factory::create();

        /**
         * @var TaxService
         */
        $taxService = app()->make( TaxService::class );

        /**
         * @var CurrencyService
         */
        $currencyService = app()->make( CurrencyService::class );

        $taxType = Arr::random( [ 'inclusive', 'exclusive' ] );
        $taxGroup = TaxGroup::get()->random();
        $margin = 25;

        $this->procurementService->create( [
            'name' => sprintf( __( 'Sample Procurement %s' ), Str::random( 5 ) ),
            'general' => [
                'provider_id' => Provider::get()->random()->id,
                'payment_status' => Procurement::PAYMENT_PAID,
                'delivery_status' => Procurement::DELIVERED,
                'automatic_approval' => 1,
            ],
            'products' => Product::withStockEnabled()
                ->notGrouped()
                ->with( 'unitGroup' )
                ->get()
                ->map( function ( $product ) {
                    return $product->unitGroup->units->map( function ( $unit ) use ( $product ) {
                        $unitQuantity = $product->unit_quantities->filter( fn( $q ) => (int) $q->unit_id === (int) $unit->id )->first();

                        return (object) [
                            'unit' => $unit,
                            'unitQuantity' => $unitQuantity,
                            'product' => $product,
                        ];
                    } );
                } )->flatten()->map( function ( $data ) use ( $taxService, $taxType, $taxGroup, $margin, $faker ) {
                    return [
                        'product_id' => $data->product->id,
                        'gross_purchase_price' => 15,
                        'net_purchase_price' => 16,
                        'purchase_price' => $taxService->getTaxGroupComputedValue(
                            $taxType,
                            $taxGroup,
                            $data->unitQuantity->sale_price - $taxService->getPercentageOf(
                                $data->unitQuantity->sale_price,
                                $margin
                            )
                        ),
                        'quantity' => $faker->numberBetween( 500, 1000 ),
                        'tax_group_id' => $taxGroup->id,
                        'tax_type' => $taxType,
                        'tax_value' => $taxService->getTaxGroupVatValue(
                            $taxType,
                            $taxGroup,
                            $data->unitQuantity->sale_price - $taxService->getPercentageOf(
                                $data->unitQuantity->sale_price,
                                $margin
                            )
                        ),
                        'total_purchase_price' => $taxService->getTaxGroupComputedValue(
                            $taxType,
                            $taxGroup,
                            $data->unitQuantity->sale_price - $taxService->getPercentageOf(
                                $data->unitQuantity->sale_price,
                                $margin
                            )
                        ) * 250,
                        'unit_id' => $data->unit->id,
                    ];
                } ),
        ] );
    }

    public function createSales()
    {
        /**
         * @var ReportService $reportService
         */
        $reportService = app()->make( ReportService::class );
        $dates = [];
        $startOfRange = ns()->date->clone()->subDays( $this->daysRange );

        for ( $i = 0; $i <= $this->daysRange; $i++ ) {
            $dates[] = $startOfRange->clone();
            $startOfRange->addDay();
        }

        $allProducts = Product::with( 'unit_quantities' )->get();
        $allCustomers = Customer::get();

        for ( $i = 0; $i < $this->orderCount; $i++ ) {
            $currentDate = Arr::random( $dates );

            /**
             * @var CurrencyService
             */
            $currency = app()->make( CurrencyService::class );
            $faker = Factory::create();

            $shippingFees = $faker->randomElement( [10, 15, 20, 25, 30, 35, 40] );
            $discountRate = $faker->numberBetween( 0, 5 );

            $products = $allProducts->shuffle()->take( 3 );

            $products = $products->map( function ( $product ) use ( $faker ) {
                $unitElement = $faker->randomElement( $product->unit_quantities );

                return array_merge( [
                    'product_id' => $product->id,
                    'quantity' => $faker->numberBetween( 1, 10 ),
                    'unit_price' => $unitElement->sale_price,
                    'unit_quantity_id' => $unitElement->id,
                ], $this->customProductParams );
            } );

            /**
             * testing customer balance
             */
            $customer = $allCustomers->random();
            $customerFirstPurchases = $customer->purchases_amount;
            $customerFirstOwed = $customer->owed_amount;

            $subtotal = ns()->currency->define( $products->map( function ( $product ) use ( $currency ) {
                return $currency
                    ->define( $product[ 'unit_price' ] )
                    ->multiplyBy( $product[ 'quantity' ] )
                    ->toFloat();
            } )->sum() )->toFloat();

            $customerCoupon = CustomerCoupon::get()->last();

            if ( $customerCoupon instanceof CustomerCoupon ) {
                $allCoupons = [
                    [
                        'customer_coupon_id' => $customerCoupon->id,
                        'coupon_id' => $customerCoupon->coupon_id,
                        'name' => $customerCoupon->name,
                        'type' => 'percentage_discount',
                        'code' => $customerCoupon->code,
                        'limit_usage' => $customerCoupon->coupon->limit_usage,
                        'value' => $currency->define( $customerCoupon->coupon->discount_value )
                            ->multiplyBy( $subtotal )
                            ->divideBy( 100 )
                            ->toFloat(),
                        'discount_value' => $customerCoupon->coupon->discount_value,
                        'minimum_cart_value' => $customerCoupon->coupon->minimum_cart_value,
                        'maximum_cart_value' => $customerCoupon->coupon->maximum_cart_value,
                    ],
                ];

                $totalCoupons = collect( $allCoupons )->map( fn( $coupon ) => $coupon[ 'value' ] )->sum();
            } else {
                $allCoupons = [];
                $totalCoupons = 0;
            }

            $discountValue = $currency->define( $discountRate )
                ->multiplyBy( $subtotal )
                ->divideBy( 100 )
                ->toFloat();

            $discountCoupons = $currency->define( $discountValue )
                ->additionateBy( $allCoupons[0][ 'value' ] ?? 0 )
                ->toFloat();

            $dateString = $currentDate->startOfDay()->addHours(
                $faker->numberBetween( 0, 23 )
            )->format( 'Y-m-d H:m:s' );

            $orderData = array_merge( [
                'customer_id' => $customer->id,
                'type' => [ 'identifier' => 'takeaway' ],
                'discount_type' => 'percentage',
                'created_at' => $this->customDate ? $dateString : null,
                'discount_percentage' => $discountRate,
                'addresses' => [
                    'shipping' => [
                        'first_name' => 'Paul',
                        'last_name' => 'Walker',
                        'country' => 'Cameroon',
                    ],
                    'billing' => [
                        'first_name' => 'EBENE Voundi',
                        'last_name' => 'Antony Hervé',
                        'country' => 'United State Seattle',
                    ],
                ],
                'coupons' => $allCoupons,
                'subtotal' => $subtotal,
                'shipping' => $shippingFees,
                'products' => $products->toArray(),
                'payments' => $this->shouldMakePayment ? [
                    [
                        'identifier' => 'cash-payment',
                        'value' => $currency->define( $subtotal )
                            ->additionateBy( $shippingFees )
                            ->subtractBy(
                                $discountCoupons
                            )
                            ->toFloat(),
                    ],
                ] : [],
            ], $this->customOrderParams );

            $result = $this->orderService->create( $orderData );
        }
    }
}
