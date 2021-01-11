<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Role;
use App\Models\Unit;
use App\Services\ProductService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Modules\NsMultiStore\Models\Store;
use Modules\NsStockTransfers\Models\StockTransfer;
use Tests\TestCase;

class StoreApproveStockTransferTest extends TestCase
{
    /**
     * @var ProductService
     */
    public $productService;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testStockTransferApproval()
    {
        Sanctum::actingAs(
            Role::namespace( 'admin' )->users->first(),
            ['*']
        );

        $this->productService       =   app()->make( ProductService::class );
        $sourceStore                =   Store::first();
        $destinationStore           =   Store::where( 'id', '<>',  $sourceStore->id )->first();

        Store::switchTo( $sourceStore );

        $products           =   Product::withStockEnabled()
            ->get()
            ->take(2);

        $response       =   $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'post', 'api/stock-transfers/submit', [
                'name'          =>  __( 'Test Stock Transfers - ' ) . Str::random(10),
                'general'       =>  [
                    'from_store'    =>  $sourceStore->id,
                    'to_store'      =>  $destinationStore->id,
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

        /**
         * switch to the destination store in order to approve the stock transfer
         * we need to check if the provided store was existing and what was 
         * the provided stock at that moment.
         */
        Store::switchTo( $destinationStore );

        $products->each( function( Product $product ) {
            $destinationStoreProduct    =   Product::sku( $product->sku )->first();

            $product->originalQuantity      =   0;
            if ( $destinationStoreProduct instanceof Product ) {
                $product->originalQuantity   =   $this->productService->getQuantity( 
                    $destinationStoreProduct->id, 
                    Unit::identifier( $product->unit_quantities[0]->unit->identifier )->first()->id
                );
            }
        });

        $response       =   $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'get', 'api/stock-transfers/approve/' . $responseData[ 'data' ][ 'transaction' ][ 'id' ] );

        Store::switchTo( $destinationStore );

        $products->each( function( $product ) use ( $response ) {
            $destinationStoreProduct    =   Product::sku( $product->sku )->first();
            
            $product->newQuantity      =   0;

            if ( $destinationStoreProduct instanceof Product ) {
                $product->newQuantity   =   $this->productService->getQuantity( 
                    $destinationStoreProduct->id, 
                    Unit::identifier( $product->unit_quantities[0]->unit->identifier )->first()->id
                );
            }

            if ( ( float )( $product->newQuantity - $product->originalQuantity ) != ( float ) 2 ) {
                throw new Exception( sprintf( 
                    __( 'After transfect is approved, the stock has\'nt been updated : before => %s, after => %s, provided => %s, expected => %s'), 
                    $product->originalQuantity, 
                    $product->newQuantity,
                    2,
                    $product->originalQuantity + 2
                ) );
            }
        });

        $response->assertJsonPath( 'status', 'success' );
    }
}
