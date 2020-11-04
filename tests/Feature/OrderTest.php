<?php

namespace Tests\Feature;

use App\Jobs\ComputeCashierSalesJob;
use App\Jobs\ComputeCustomerAccountJob;
use App\Jobs\ComputeDayReportJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Models\Product;
use App\Models\User;
use App\Services\CurrencyService;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testPostingOrder()
    {
        Sanctum::actingAs(
            User::find(98),
            ['*']
        );

        $this->expectsJobs([
            ComputeDayReportJob::class,
            ComputeCustomeerAccountJob::class,
            ComputeCashierSalesJob::class,
        ]);

        $currency       =   app()->make( CurrencyService::class );
        $product        =   Product::with( 'unit_quantities' )->find(1);
        $shippingFees   =   150;
        $discountRate   =   3.5;
        $products       =   [
            [
                'product_id'            =>  $product->id,
                'quantity'              =>  5,
                'unit_price'            =>  $product->unit_quantities[0]->sale_price,
                'unit_quantity_id'      =>  $product->unit_quantities[0]->id,
            ]
        ];

        $subtotal   =   collect( $products )->map( fn( $product ) => $product[ 'unit_price' ] * $product[ 'quantity' ] )->sum();

        $response   =   $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/orders', [
                'customer_id'           =>  1,
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
                'subtotal'              =>  $subtotal,
                'shipping'              =>  $shippingFees,
                'products'              =>  $products,
                'payments'              =>  [
                    [
                        'identifier'    =>  'cash-payment',
                        'amount'        =>  $subtotal + $shippingFees
                    ]
                ]
            ]);
        
        $response->assertJson([
            'status'    =>  'success'
        ]);

        $discount       =   ( ( $discountRate * $subtotal ) / 100 );
        $netsubtotal    =   $subtotal - $discount;

        $response->assertJsonPath( 'data.order.subtotal',   $currency->getRaw( $subtotal ) );
        $response->assertJsonPath( 'data.order.total',      $currency->getRaw( $netsubtotal + $shippingFees ) );
        $response->assertJsonPath( 'data.order.change',     $currency->getRaw( ( $subtotal + $shippingFees ) - ( $netsubtotal + $shippingFees ) ) );
    }
}
