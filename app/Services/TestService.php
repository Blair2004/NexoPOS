<?php
namespace App\Services;

use App\Models\Customer;
use App\Models\Procurement;
use App\Models\Product;
use App\Models\Provider;
use App\Models\TaxGroup;
use App\Models\User;
use Carbon\Carbon;
use Faker\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class TestService
{
    public function prepareOrder( Carbon $date, array $orderDetails = [], array $productDetails = [], array $config = [] )
    {
        /**
         * @var CurrencyService
         */
        $currency       =   app()->make( CurrencyService::class );
        $faker          =   Factory::create();
        $products       =   isset( $config[ 'products' ] ) ? $config[ 'products' ]() : Product::where( 'tax_group_id', '>', 0 )->with( 'unit_quantities' )->get()->shuffle()->take(3);
        $shippingFees   =   $faker->randomElement([10,15,20,25,30,35,40]);
        $discountRate   =   $faker->numberBetween(0,5);

        $products           =   $products->map( function( $product ) use ( $faker, $productDetails, $config ) {
            $unitElement    =   $faker->randomElement( $product->unit_quantities );

            $data           =   array_merge([
                'name'                  =>  'Fees',
                'quantity'              =>  $faker->numberBetween(1,10),
                'unit_price'            =>  $unitElement->sale_price,
                'tax_type'              =>  'inclusive',
                'tax_group_id'          =>  1,
                'unit_id'               =>  $unitElement->unit_id,
            ], $productDetails );

            if ( $faker->randomElement([ false, true ]) || ! ( $config[ 'allow_quick_products' ] ?? true ) ) {
                $data[ 'product_id' ]       =   $product->id;
                $data[ 'unit_quantity_id' ] =   $unitElement->id;
            }

            return $data;
        })->filter( function( $product ) {
            return $product[ 'quantity' ] > 0;
        });

        /**
         * testing customer balance
         */
        $customer                   =   Customer::get()->random();

        $subtotal   =   ns()->currency->getRaw( $products->map( function( $product ) use ($currency) {
            return $currency
                ->define( $product[ 'unit_price' ] )
                ->multiplyBy( $product[ 'quantity' ] )
                ->getRaw();
        })->sum() );

        $discount           =   [
            'type'      =>      $faker->randomElement([ 'percentage', 'flat' ]),
        ];

        /**
         * If the discount is percentage or flat.
         */
        if ( $discount[ 'type' ] === 'percentage' ) {
            $discount[ 'rate' ]     =   $discountRate;
            $discount[ 'value' ]    =   $currency->define( $discount[ 'rate' ] )
                ->multiplyBy( $subtotal )
                ->divideBy( 100 )
                ->getRaw();
        } else {
            $discount[ 'value' ]    =   10;
            $discount[ 'rate' ]     =   0;
        }

        $dateString         =   $date->startOfDay()->addHours( 
            $faker->numberBetween( 0,23 ) 
        )->format( 'Y-m-d H:m:s' );

        return array_merge([
            'customer_id'           =>  $customer->id,
            'type'                  =>  [ 'identifier' => 'takeaway' ],
            'discount_type'         =>  $discount[ 'type' ],
            'created_at'            =>  $dateString,
            'discount_percentage'   =>  $discount[ 'rate' ] ?? 0,
            'discount'              =>  $discount[ 'value' ] ?? 0,
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
            'author'                =>  User::get( 'id' )->pluck( 'id' )->shuffle()->first(),
            'coupons'               =>  [],
            'subtotal'              =>  $subtotal,
            'shipping'              =>  $shippingFees,
            'products'              =>  $products->toArray(),
            'payments'              =>  [
                [
                    'identifier'    =>  'cash-payment',
                    'value'         =>  $currency->define( $subtotal )
                        ->additionateBy( $shippingFees )
                        ->getRaw()
                ]
            ]
        ], $orderDetails );
    }

    public function prepareProcurement( Carbon $date, array $details = [] )
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
        
        return array_merge([
            'name'                  =>  sprintf( __( 'Sample Procurement %s' ), Str::random(5) ),
            'general'   =>  [
                'provider_id'           =>  Provider::get()->random()->id,
                'payment_status'        =>  Procurement::PAYMENT_PAID,
                'delivery_status'       =>  Procurement::DELIVERED,
                'author'                =>  Auth::id(), // @todo is that required
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

                $quantity   =   $faker->numberBetween(800,1500);

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
                    'quantity'              =>  $quantity,
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
                    ) * $quantity,
                    'unit_id'               =>  $data->unit->id,
                ];
            })
        ], $details );
    }
}