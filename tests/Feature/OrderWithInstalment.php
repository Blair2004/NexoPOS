<?php

namespace Tests\Feature;

use App\Classes\Currency;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderInstalment;
use App\Models\Product;
use App\Models\Role;
use App\Services\CurrencyService;
use App\Services\OrdersService;
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

        /**
         * @var OrdersService
         */
        $orderService   =   app()->make( OrdersService::class );
        $faker          =   Factory::create();
        $products       =   Product::with( 'unit_quantities' )->get()->shuffle()->take(1);
        $shippingFees   =   $faker->randomElement([100,150,200,250,300,350,400]);
        // $shippingFees   =   200;
        $discountRate   =   $faker->numberBetween(1,5);

        $products       =   $products->map( function( $product ) use ( $faker ) {
            $unitElement    =   $faker->randomElement( $product->unit_quantities );
            return [
                'product_id'            =>  $product->id,
                'quantity'              =>  $faker->numberBetween(1,10), // 2,
                'unit_price'            =>  $unitElement->sale_price, // 110.8402,
                'unit_quantity_id'      =>  $unitElement->id,
            ];
        });

        /**
         * testing customer balance
         */
        $customer                   =   Customer::first();

        $subtotal   =   ns()->currency->getRaw( $products->map( function( $product ) use ($currency) {
            return Currency::raw( $product[ 'unit_price' ] ) * Currency::raw( $product[ 'quantity' ] );
        })->sum() );

        $initialTotalInstallment    =   2;
        $discountValue              =   $orderService->computeDiscountValues( $discountRate, $subtotal );
        // $discountValue              =   Currency::raw( 2.1504 );
        $total                      =   ns()->currency->getRaw( ( $subtotal + $shippingFees ) - $discountValue );

        $paymentAmount              =   ns()->currency->getRaw( ( ( $subtotal + $shippingFees ) - $discountValue ) / 2 );

        // ( ( $subtotal + $shippingFees ) - $discountValue ) / 2
        $instalmentPayment          =   ns()->currency->getRaw( ( ( $subtotal + $shippingFees ) - $discountValue ) / 2 );

        dump( 'subtotal => ' . $subtotal );
        dump( 'discount => ' . $discountValue );
        dump( 'shippingFees => ' . $shippingFees );
        dump( 'total => ' . $total );
        dump( 'installment => ' . ns()->currency->getRaw( ( ( $subtotal + $shippingFees ) - $discountValue ) / 2 ) );
        dump( 'payment =>' . $paymentAmount );

        $response   =   $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/orders', [
                'customer_id'           =>  $customer->id,
                'type'                  =>  [ 'identifier' => 'takeaway' ],
                // 'discount_type'         =>  'percentage',
                'discount_percentage'   =>  $discountRate,
                'discount_type'         =>  'flat',
                'discount'              =>  $discountValue,
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
                'total'                 =>  $total,
                'tendered'              =>  ns()->currency
                    ->getRaw( ( ( $subtotal + $shippingFees ) - $discountValue ) / 2 ),
                'total_instalments'     =>  $initialTotalInstallment,
                'instalments'           =>  [
                    [
                        'date'          =>  ns()->date->getNowFormatted(),
                        'amount'        =>  $instalmentPayment
                    ], [
                        'date'          =>  ns()->date->copy()->addDays(2)->toDateTimeString(),
                        'amount'        =>  $instalmentPayment
                    ]
                ],
                'products'              =>  $products->toArray(),
                'payments'              =>  [
                    [
                        'identifier'    =>  'cash-payment',
                        'value'         =>  $paymentAmount,
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
        $today              =   ns()->date->toDateTimeString();
        $order              =   $responseData[ 'data' ][ 'order' ];
        $instalment         =   OrderInstalment::where( 'order_id', $order[ 'id' ] )->where( 'paid', false )->get()->random();
        $instalmentAmount   =   ns()->currency->getRaw( $instalment->amount / 2 );
        $response           =   $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'PUT', 'api/nexopos/v4/orders/' . $order[ 'id' ] . '/instalments/' . $instalment->id, [
                'instalment'    =>  [
                    'date'      =>  $today,
                    'amount'    =>  $instalmentAmount
                ]
            ]);

        $response->assertJson([
            'status'    =>  'success'
        ]);

        $instalment->refresh();

        if ( $instalment->date != $today ) {
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
                    'date'      =>  $today,
                    'amount'    =>  $instalmentAmount
                ]
            ]);

        $response->assertJson([
            'status'    =>  'success'
        ]);

        $order->refresh();

        if ( $initialTotalInstallment === $order->total_instalments ) {
            throw new Exception( __( 'The instalment hasn\'t been registered.' ) );
        }

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

        if ( $oldInstlaments === $order->total_instalments ) {
            throw new Exception( __( 'The instalment hasn\'t been deleted.' ) );
        }

        /**
         * restore deleted instalment
         */
        $order          =   Order::find( $order[ 'id' ] );
        $oldInstlaments =   $order->total_instalments;
        $response       =   $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/orders/' . $order[ 'id' ] . '/instalments', [
                'instalment'    =>  [
                    'date'      =>  $today,
                    'amount'    =>  $instalmentAmount
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
         * paying instalment
         */
        
        OrderInstalment::where( 'order_id', $order->id )
            ->where( 'paid', false )
            ->get()
            ->each( function( $instalment ) use ( $order ) {
                $response       =   $this->withSession( $this->app[ 'session' ]->all() )
                    ->json( 'GET', 'api/nexopos/v4/orders/' . $order->id . '/instalments/' . $instalment->id . '/paid' );
                $response->assertJson([
                    'status'    =>  'success'
                ]);

                $instalment->refresh();

                $this->assertTrue( $instalment->paid, __( 'The instalment hasn\'t been paid.' ) );
        });
    }
}
