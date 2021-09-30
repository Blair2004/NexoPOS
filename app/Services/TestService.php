<?php
namespace App\Services;

use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Faker\Factory;

class TestService
{
    public function prepareOrder( Carbon $date, array $orderDetails = [], array $productDetails = [] )
    {
        /**
         * @var CurrencyService
         */
        $currency       =   app()->make( CurrencyService::class );
        $faker          =   Factory::create();
        $products       =   Product::where( 'tax_group_id', '>', 0 )->with( 'unit_quantities' )->get()->shuffle()->take(3);
        $shippingFees   =   $faker->randomElement([10,15,20,25,30,35,40]);
        $discountRate   =   $faker->numberBetween(0,5);

        $products           =   $products->map( function( $product ) use ( $faker, $productDetails ) {
            $unitElement    =   $faker->randomElement( $product->unit_quantities );

            $data           =   array_merge([
                'name'                  =>  'Fees',
                'quantity'              =>  $faker->numberBetween(1,10),
                'unit_price'            =>  $unitElement->sale_price,
                'tax_type'              =>  'inclusive',
                'tax_group_id'          =>  1,
                'unit_id'               =>  $unitElement->unit_id,
            ], $productDetails );

            if ( $faker->randomElement([ false, true ]) ) {
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
}