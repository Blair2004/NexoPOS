<?php

namespace Tests\Feature\Mcp;

use App\Mcp\Tools\GetProductTool;
use App\Models\Product;
use App\Models\TaxGroup;
use App\Models\UnitGroup;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Traits\TestsMcpTools;

class ProductsMcpTest extends TestCase
{
    use DatabaseTransactions, TestsMcpTools;

    public function test_get_product_by_id()
    {
        TaxGroup::factory()->create();
        UnitGroup::factory()->create();

        $product = Product::factory()->create();

        $response = $this->runMcpTool( GetProductTool::class, [
            'id' => $product->id,
        ] );

        $this->assertIsArray( $response );
        $this->assertArrayNotHasKey( 'error', $response );
        $this->assertEquals( $product->id, $response['id'] );
    }

    public function test_get_product_by_barcode()
    {
        $product = Product::factory()->create( ['barcode' => '123456789'] );

        $response = $this->runMcpTool( GetProductTool::class, [
            'barcode' => '123456789',
        ] );

        $this->assertIsArray( $response );
        $this->assertEquals( $product->id, $response['id'], 'Returned product id should match created product id' );
    }

    public function test_get_product_missing_identifier()
    {
        $response = $this->runMcpTool( GetProductTool::class, [] );

        $this->assertIsArray( $response );
        $this->assertArrayHasKey( 'error', $response );
        $this->assertTrue( $response['error'] );
        $this->assertEquals( 'Provide at least one of: id, barcode, or sku.', $response['message'] );
    }
}
