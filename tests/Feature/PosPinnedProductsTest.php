<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductUnitQuantity;
use App\Models\Unit;
use Tests\TestCase;
use Tests\Traits\WithAuthentication;

class PosPinnedProductsTest extends TestCase
{
    use WithAuthentication;

    public function test_it_returns_only_sale_eligible_visible_pinned_products(): void
    {
        $this->attemptAuthenticate();
        $previousHideExhausted = ns()->option->get( 'ns_pos_hide_exhausted_products' );
        ns()->option->set( 'ns_pos_hide_exhausted_products', 'yes' );

        $eligible = $this->createProduct( true, Product::STATUS_AVAILABLE, Product::STOCK_MANAGEMENT_DISABLED );
        $unpinned = $this->createProduct( false, Product::STATUS_AVAILABLE, Product::STOCK_MANAGEMENT_DISABLED );
        $unavailable = $this->createProduct( true, Product::STATUS_UNAVAILABLE, Product::STOCK_MANAGEMENT_DISABLED );
        $exhausted = $this->createProduct( true, Product::STATUS_AVAILABLE, Product::STOCK_MANAGEMENT_ENABLED );
        $quantity = ProductUnitQuantity::query()->forceCreate( [
            'product_id' => $exhausted->id,
            'unit_id' => Unit::query()->firstOrFail()->id,
            'quantity' => 0,
            'visible' => true,
        ] );

        try {
            $response = $this->withSession( $this->app['session']->all() )
                ->getJson( '/api/products/pos/pinned' );

            $response->assertOk()
                ->assertJsonStructure( [ 'pinnedProducts' ] )
                ->assertJsonFragment( [ 'id' => $eligible->id ] )
                ->assertJsonMissing( [ 'id' => $unpinned->id ] )
                ->assertJsonMissing( [ 'id' => $unavailable->id ] )
                ->assertJsonMissing( [ 'id' => $exhausted->id ] );
        } finally {
            $quantity->delete();
            Product::query()->whereKey( [ $eligible->id, $unpinned->id, $unavailable->id, $exhausted->id ] )->delete();
            ns()->option->set( 'ns_pos_hide_exhausted_products', $previousHideExhausted );
        }
    }

    private function createProduct( bool $pinned, string $status, string $stockManagement ): Product
    {
        return Product::factory()->create( [
            'pinned' => $pinned,
            'status' => $status,
            'accurate_tracking' => false,
            'stock_management' => $stockManagement,
        ] );
    }
}
