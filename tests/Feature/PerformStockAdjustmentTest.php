<?php

namespace Tests\Feature;

use App\Models\ProductHistory;
use App\Models\ProductUnitQuantity;
use App\Models\Role;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PerformStockAdjustmentTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_increase_product_stock()
    {
        Sanctum::actingAs(
            Role::namespace( 'admin' )->users->first(),
            ['*']
        );

        $productQuantity    =   ProductUnitQuantity::where( 'quantity', '>', 0 )->first();
        
        if ( ! $productQuantity instanceof ProductUnitQuantity ) {
            throw new Exception( __( 'Unable to find a product to perform this test.' ) );
        }

        $product    =   $productQuantity->product;

        foreach( ProductHistory::STOCK_INCREASE as $action ) {
            $response       =   $this->withSession( $this->app[ 'session' ]->all() )
                ->json( 'POST', 'api/nexopos/v4/products/adjustments', [
                    'products'  =>  [
                        [
                            'adjust_action'     =>  $action,
                            'adjust_unit'       =>  [
                                'sale_price'    =>  $productQuantity->sale_price,
                                'unit_id'       =>  $productQuantity->unit_id,
                            ],
                            'id'                =>  $product->id,
                            'adjust_quantity'   =>  10,
                        ]
                    ]
                ]);

            $oldQuantity    =   $productQuantity->quantity;
            $productQuantity->refresh();

            $response->assertStatus(200);

            $this->assertTrue( 
                $productQuantity->quantity - $oldQuantity === ( float ) 10, 
                sprintf(
                    __( 'The stock modification : %s hasn\'t made any change' ),
                    $action
                )
            );
        }

    }

    public function test_decreate_product_stock()
    {
        Sanctum::actingAs(
            Role::namespace( 'admin' )->users->first(),
            ['*']
        );

        $productQuantity    =   ProductUnitQuantity::where( 'quantity', '>', 0 )->first();
        
        if ( ! $productQuantity instanceof ProductUnitQuantity ) {
            throw new Exception( __( 'Unable to find a product to perform this test.' ) );
        }

        $product    =   $productQuantity->product;

        foreach( ProductHistory::STOCK_REDUCE as $action ) {
            $response       =   $this->withSession( $this->app[ 'session' ]->all() )
                ->json( 'POST', 'api/nexopos/v4/products/adjustments', [
                    'products'  =>  [
                        [
                            'adjust_action'     =>  $action,
                            'adjust_unit'       =>  [
                                'sale_price'    =>  $productQuantity->sale_price,
                                'unit_id'       =>  $productQuantity->unit_id,
                            ],
                            'id'                =>  $product->id,
                            'adjust_quantity'   =>  1,
                        ]
                    ]
                ]);

            $oldQuantity    =   $productQuantity->quantity;
            $productQuantity->refresh();

            $response->assertStatus(200);

            $this->assertTrue( 
                $oldQuantity - $productQuantity->quantity === ( float ) 1, 
                sprintf(
                    __( 'The stock modification : %s hasn\'t made any change' ),
                    $action
                )
            );
        }
    }
}
