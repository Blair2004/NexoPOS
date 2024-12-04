<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Procurement;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductUnitQuantity;
use App\Models\Provider;
use App\Models\TaxGroup;
use App\Models\UnitGroup;
use App\Models\User;
use Carbon\Carbon;
use Faker\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TestService
{
    public function prepareProduct( $data = [] )
    {
        $faker = Factory::create();
        $category = ProductCategory::get()->random();
        $unitGroup = UnitGroup::get()->random();
        $taxGroup = TaxGroup::get()->random();

        return array_merge( [
            'name' => ucwords( $faker->word ),
            'variations' => [
                [
                    '$primary' => true,
                    'expiracy' => [
                        'expires' => 0,
                        'on_expiration' => 'prevent_sales',
                    ],
                    'groups' => [],
                    'identification' => [
                        'barcode' => $faker->ean13(),
                        'barcode_type' => 'ean13',
                        'searchable' => $faker->randomElement( [ true, false ] ),
                        'category_id' => $category->id,
                        'description' => __( 'Created via tests' ),
                        'product_type' => 'product',
                        'type' => $faker->randomElement( [ Product::TYPE_MATERIALIZED, Product::TYPE_DEMATERIALIZED ] ),
                        'sku' => Str::random( 15 ) . '-sku',
                        'status' => 'available',
                        'stock_management' => 'enabled',
                    ],
                    'images' => [],
                    'taxes' => [
                        'tax_group_id' => 1,
                        'tax_type' => Arr::random( [ 'inclusive', 'exclusive' ] ),
                    ],
                    'units' => [
                        'selling_group' => $unitGroup->units->map( function ( $unit ) use ( $faker ) {
                            return [
                                'sale_price_edit' => $faker->numberBetween( 20, 25 ),
                                'wholesale_price_edit' => $faker->numberBetween( 20, 25 ),
                                'unit_id' => $unit->id,
                            ];
                        } )->toArray(),
                        'unit_group' => $unitGroup->id,
                    ],
                ],
            ],
        ], $data );
    }

    public function prepareOrder( ?Carbon $date = null, array $orderDetails = [], array $productDetails = [], array $config = [] )
    {
        $date = $date ?? now();

        /**
         * @var CurrencyService
         */
        $currency = app()->make( CurrencyService::class );
        $faker = Factory::create();
        $products = isset( $config[ 'products' ] ) ? $config[ 'products' ]() : Product::where( 'tax_group_id', '>', 0 )
            ->where( 'type', '<>', Product::TYPE_GROUPED )
            ->whereRelation( 'unit_quantities', 'quantity', '>', 1000 )
            ->with( 'unit_quantities', function ( $query ) {
                $query->where( 'quantity', '>', 3 );
            } )
            ->get()
            ->shuffle()
            ->take( 3 );
        $shippingFees = $faker->randomElement( [10, 15, 20, 25, 30, 35, 40] );
        $discountRate = $faker->numberBetween( 0, 5 );

        $products = $products->map( function ( $product ) use ( $faker, $productDetails, $config ) {
            $unitElement = $faker->randomElement( $product->unit_quantities );

            $data = array_merge( [
                'name' => $product->name,
                'quantity' => $product->quantity ?? $faker->numberBetween( 1, 3 ),
                'unit_price' => $unitElement->sale_price,
                'tax_type' => 'inclusive',
                'tax_group_id' => 1,
                'unit_id' => $unitElement->unit_id,
            ], $productDetails );

            if (
                ( isset( $product->id ) ) ||
                ( $faker->randomElement( [ false, true ] ) && ! ( $config[ 'allow_quick_products' ] ?? true ) )
            ) {
                $data[ 'product_id' ] = $product->id;
                $data[ 'unit_quantity_id' ] = $unitElement->id;
            }

            return $data;
        } )->filter( function ( $product ) {
            return $product[ 'quantity' ] > 0;
        } );

        /**
         * testing customer balance
         */
        $customer = Customer::get()->random();

        $subtotal = ns()->currency->define( $products->map( function ( $product ) use ( $currency ) {
            return $currency
                ->define( $product[ 'unit_price' ] )
                ->multiplyBy( $product[ 'quantity' ] )
                ->toFloat();
        } )->sum() )->toFloat();

        $discount = [
            'type' => $faker->randomElement( [ 'percentage', 'flat' ] ),
        ];

        /**
         * If the discount is percentage or flat.
         */
        if ( $discount[ 'type' ] === 'percentage' ) {
            $discount[ 'rate' ] = $discountRate;
            $discount[ 'value' ] = $currency->define( $discount[ 'rate' ] )
                ->multiplyBy( $subtotal )
                ->divideBy( 100 )
                ->toFloat();
        } else {
            $discount[ 'value' ] = 2;
            $discount[ 'rate' ] = 0;
        }

        $dateString = $date->startOfDay()->addHours(
            $faker->numberBetween( 0, 23 )
        )->format( 'Y-m-d H:m:s' );

        $finalDetails = [
            'customer_id' => $customer->id,
            'type' => [ 'identifier' => 'takeaway' ],
            'discount_type' => $discount[ 'type' ],
            'created_at' => $dateString,
            'discount_percentage' => $discount[ 'rate' ] ?? 0,
            'discount' => $discount[ 'value' ] ?? 0,
            'addresses' => [
                'shipping' => [
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'country' => 'Cameroon',
                ],
                'billing' => [
                    'first_name' => 'EBENE Voundi',
                    'last_name' => 'Antony Hervé',
                    'country' => 'United State Seattle',
                ],
            ],
            'author' => User::get( 'id' )->pluck( 'id' )->shuffle()->first(),
            'coupons' => [],
            'subtotal' => $subtotal,
            'shipping' => $shippingFees,
            'products' => $products->toArray(),
        ];

        if ( isset( $config[ 'payments' ] ) && is_callable( $config[ 'payments' ] ) ) {
            $finalDetails[ 'payments' ] = $config[ 'payments' ]( $finalDetails );
        } else {
            $finalDetails[ 'payments' ] = [
                [
                    'identifier' => 'cash-payment',
                    'value' => $currency->define( $subtotal )
                        ->additionateBy( $shippingFees )
                        ->toFloat(),
                ],
            ];
        }

        return array_merge( $finalDetails, $orderDetails );
    }

    public function prepareProcurement( Carbon $date, array $details = [] )
    {
        $faker = Factory::create();

        /**
         * @var TaxService
         */
        $taxService = app()->make( TaxService::class );

        $taxType = Arr::random( [ 'inclusive', 'exclusive' ] );
        $taxGroup = TaxGroup::get()->random();
        $margin = 25;
        $request = Product::withStockEnabled()
            ->notGrouped()
            ->limit( $details[ 'total_products' ] ?? -1 )
            ->with( [
                'unitGroup',
                'unit_quantities' => function ( $query ) use ( $details ) {
                    $query->limit( $details[ 'total_unit_quantities' ] ?? -1 );
                },
            ] )
            ->get();

        $config = [
            'name' => sprintf( __( 'Sample Procurement %s' ), Str::random( 5 ) ),
            'general' => [
                'provider_id' => Provider::get()->random()->id,
                'payment_status' => Procurement::PAYMENT_PAID,
                'delivery_status' => Procurement::DELIVERED,
                'author' => Auth::id(), // @todo is that required
                'automatic_approval' => 1,
                'created_at' => $date->toDateTimeString(),
            ],
            'products' => $request->map( function ( $product ) {
                return $product->unitGroup->units->map( function ( $unit ) use ( $product ) {
                    // we retreive the unit quantity only if that is included on the group units.
                    $unitQuantity = $product->unit_quantities->filter( fn( $q ) => (int) $q->unit_id === (int) $unit->id )->first();

                    if ( $unitQuantity instanceof ProductUnitQuantity ) {
                        return (object) [
                            'unit' => $unit,
                            'unitQuantity' => $unitQuantity,
                            'product' => $product,
                        ];
                    }

                    return false;
                } )->filter();
            } )->flatten()->map( function ( $data ) use ( $taxService, $taxType, $taxGroup, $margin, $faker ) {
                $quantity = $faker->numberBetween( 1000, 9999 );

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
                    'quantity' => $quantity,
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
                    ) * $quantity,
                    'unit_id' => $data->unit->id,
                ];
            } ),
        ];

        foreach ( $details as $key => $value ) {
            Arr::set( $config, $key, $value );
        }

        return $config;
    }
}
