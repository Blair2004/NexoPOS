<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderInstalment;
use App\Models\Product;
use App\Models\Role;
use App\Services\CurrencyService;
use Carbon\Carbon;
use Exception;
use Faker\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderWithInstalment extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testExample()
    {
        Sanctum::actingAs(
            Role::namespace( 'admin' )->users->first(),
            ['*']
        );
        
        /**
         * @var CurrencyService
         */
        $currency       =   app()->make( CurrencyService::class );
        $faker          =   Factory::create();
        $products       =   Product::with( 'unit_quantities' )->get()->shuffle()->take(3);
        $shippingFees   =   $faker->randomElement([100,150,200,250,300,350,400]);
        $discountRate   =   $faker->numberBetween(0,5);

        $products       =   $products->map( function( $product ) use ( $faker ) {
            $unitElement    =   $faker->randomElement( $product->unit_quantities );
            return [
                'product_id'            =>  $product->id,
                'quantity'              =>  $faker->numberBetween(1,10),
                'unit_price'            =>  $unitElement->sale_price,
                'unit_quantity_id'      =>  $unitElement->id,
            ];
        });

        /**
         * testing customer balance
         */
        $customer                   =   Customer::first();

        $subtotal   =   ns()->currency->getRaw( $products->map( function( $product ) use ($currency) {
            return $currency
                ->define( $product[ 'unit_price' ] )
                ->multiplyBy( $product[ 'quantity' ] )
                ->getRaw();
        })->sum() );

        $response   =   $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/orders', [
                'customer_id'           =>  $customer->id,
                'type'                  =>  [ 'identifier' => 'takeaway' ],
                'discount_type'         =>  'percentage',
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
                'coupons'               =>  [],
                'subtotal'              =>  $subtotal,
                'shipping'              =>  $shippingFees,
                'total'                 =>  $subtotal + $shippingFees,
                'tendered'              =>  ( $subtotal + $shippingFees ) / 2,
                'total_instalments'     =>  2,
                'instalments'           =>  [
                    [
                        'date'          =>  ns()->date->getNowFormatted(),
                        'amount'        =>  ( $subtotal + $shippingFees ) / 2
                    ], [
                        'date'          =>  ns()->date->copy()->addDays(2)->toDateTimeString(),
                        'amount'        =>  ( $subtotal + $shippingFees ) / 2
                    ]
                ],
                'products'              =>  $products->toArray(),
                'payments'              =>  [
                    [
                        'identifier'    =>  'cash-payment',
                        'value'         =>  $currency->define( $subtotal )
                            ->additionateBy( $shippingFees )
                            ->dividedBy(2)
                            ->getRaw()
                    ]
                ]
            ]);
        
        $response->assertJson([
            'status'    =>  'success'
        ]);

        $responseData   =   json_decode( $response->getContent(), true );

        /**
         * Editing the instalment
         */
        $order          =   $responseData[ 'data' ][ 'order' ];
        $instalment     =   OrderInstalment::where( 'order_id', $order[ 'id' ] )->where( 'paid', false )->get()->random();
        $amount         =   $instalment->amount / 2;
        $response       =   $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'PUT', 'api/nexopos/v4/orders/' . $order[ 'id' ] . '/instalments/' . $instalment->id, [
                'instalment'    =>  [
                    'date'      =>  Carbon::parse( $instalment->date )->addDay()->toDateTimeString(),
                    'amount'    =>  $amount
                ]
            ]);

        $response->assertJson([
            'status'    =>  'success'
        ]);

        $instalment->refresh();

        if ( $instalment->amount != $amount ) {
            throw new Exception( __( 'The modification of the instalment has failed' ) );
        }

        /**
         * Add instalment
         */
        $order          =   Order::find( $order[ 'id' ] );
        $oldInstlaments =   $order->total_instalments;
        $response       =   $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/orders/' . $order[ 'id' ] . '/instalments', [
                'instalment'    =>  [
                    'date'      =>  Carbon::parse( $instalment->date )->addDays(2)->toDateTimeString(),
                    'amount'    =>  $amount
                ]
            ]);

        $response->assertJson([
            'status'    =>  'success'
        ]);

        $order->refresh();

        if ( $oldInstlaments >= $order->total_instalments ) {
            throw new Exception( __( 'The instalment hasn\'t been registered.' ) );
        }

        $responseData   =   json_decode( $response->getContent(), true );

        /**
         * Delete Instalment
         */
        $order          =   Order::find( $order[ 'id' ] );
        $oldInstlaments =   $order->total_instalments;
        $response       =   $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'DELETE', 'api/nexopos/v4/orders/' . $order[ 'id' ] . '/instalments/' . $responseData[ 'data' ][ 'instalment' ][ 'id' ] );

        $response->assertJson([
            'status'    =>  'success'
        ]);

        $order->refresh();

        if ( $oldInstlaments > $order->total_instalments ) {
            throw new Exception( __( 'The instalment hasn\'t been deleted.' ) );
        }
    }
}
