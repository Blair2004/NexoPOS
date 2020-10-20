<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductAdjustmentTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testExample()
    {
        Sanctum::actingAs(
            User::find(98),
            ['*']
        );

        $product            =   Product::find(1);
        $unitQuantity       =   $product->unit_quantities[0];

        $response           =   $this->json( 'POST', '/api/nexopos/v4/products/adjustments', [
            'products'              =>  [
                [
                    'id'                =>  1,
                    'adjust_action'     =>  'deleted',
                    'name'              =>  $product->name,
                    'adjust_unit'       =>  $unitQuantity,
                    'adjust_quantity'   =>  1
                ]
            ]
        ]);

        $response->assertJsonPath( 'status', 'success' );
    }
}
