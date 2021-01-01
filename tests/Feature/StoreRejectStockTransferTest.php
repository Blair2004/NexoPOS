<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Role;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Modules\NsMultiStore\Models\Store;
use Modules\NsStockTransfers\Models\StockTransfer;
use Tests\TestCase;

class StoreRejectStockTransferTest extends TestCase
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
        $products       =   Product::withStockEnabled()
            ->get()
            ->take(2);
        $otherStore     =   Store::where( 'id', '<>',  $store->id )->first();

        ns()->store->setStore( $store );

        $response       =   $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'post', 'api/stock-transfers/submit', [
                'name'          =>  __( 'Test Stock Transfers' ),
                'general'       =>  [
                    'from_store'    =>  $store->id,
                    'to_store'      =>  $otherStore->id,
                    'type'          =>  StockTransfer::TYPE_TRANSFER,
                    'status'        =>  StockTransfer::STATUS_PENDING,
                ],
                'products'      =>  $products
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

        $responseData   =   json_decode( $response->getContent(), true );

        ns()->store->setStore( $otherStore );

        $response       =   $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'get', 'api/stock-transfers/reject/' . $responseData[ 'data' ][ 'transaction' ][ 'id' ] );

        $products->each( function( $product ) use ( $response ) {
            $oldProductQuantity     =   $product->unit_quantities[0]->quantity;
            $product->fresh();
            $newQuantity            =   $product->unit_quantities[0]->quantity;

            if ( $oldProductQuantity !== $newQuantity ) {
                throw new Exception( sprintf( __( 'After transfect is rejected, the stock is no more the same : before => %s, after => %s'), $oldProductQuantity, $newQuantity ) );
            }
        });


        $response->assertJsonPath( 'status', 'success' );

    }
}
