<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Modules\NsMultiStore\Models\Store;
use Tests\TestCase;

class StoreCreateStockTransfer extends TestCase
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

        $store          =   Store::first();
        $otherStore     =   Store::where( 'id', '<>',  $store->id )->first();

        ns()->store->setStore( $store );

        $response       =   $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/stock-transfers/submit', [
                'name'          =>  __( 'Test Stock Transfers' ),
                'general'       =>  [
                    'from_store'    =>  $store->id,
                    'to_store'      =>  $otherStore->id,
                    'type'          =>  'transfer',
                    'status'        =>  'draft',
                ],
                'products'      =>  Product::withStockEnabled()
                    ->get()
                    ->take(2)
                    ->map( function( $product ) {
                        return [
                            'name'          =>  $product->name,
                            'unit_name'     =>  $product->unit_quantities[0]->unit->name,
                            'quantity'      =>  2,
                            'unit_quantity' =>  $product->unit_quantities[0],
                            'unit_price'    =>  $product->unit_quantities[0]->sale_price,
                            'product_id'    =>  $product->id,
                            'unit_id'       =>  $product->unit_quantities[0]->unit->id,
                        ];
                    })
            ]);

        $response->assertJsonPath( 'status', 'success' );
    }
}
