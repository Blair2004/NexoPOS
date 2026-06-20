<?php

namespace Tests\Feature\Mcp;

use App\Mcp\Tools\CreateCategoryTool;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Traits\TestsMcpTools;

class CategoriesMcpTest extends TestCase
{
    use DatabaseTransactions, TestsMcpTools;

    public function test_create_category()
    {
        $response = $this->runMcpTool( CreateCategoryTool::class, [
            'name' => 'Awesome Category',
        ] );

        $this->assertIsArray( $response );
        $this->assertArrayNotHasKey( 'error', $response );

        // Should return the ID of the new category
        $this->assertArrayHasKey( 'id', $response );

        $category = ProductCategory::find( $response['id'] );
        $this->assertNotNull( $category );
        $this->assertEquals( 'Awesome Category', $category->name );
    }
}
