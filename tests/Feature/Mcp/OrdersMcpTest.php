<?php

namespace Tests\Feature\Mcp;

use App\Mcp\Tools\GetOrderTool;
use App\Mcp\Tools\SearchOrdersTool;
use App\Models\Order;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Traits\TestsMcpTools;

class OrdersMcpTest extends TestCase
{
    use DatabaseTransactions, TestsMcpTools;

    public function test_get_order()
    {
        $response = $this->runMcpTool(GetOrderTool::class, [
            'id' => 99999, // Unlikely to exist
        ]);

        dump($response);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('error', $response);
        $this->assertTrue($response['error']);
    }

    public function test_search_orders()
    {
        $response = $this->runMcpTool(SearchOrdersTool::class, [
            'search' => 'ORDER-123',
            'limit' => 5,
        ]);

        dump($response);
        $this->assertIsArray($response);
        $this->assertArrayNotHasKey('error', $response);
    }

    public function __skipped_test_search_product_sales()
    {
        $response = $this->runMcpTool(\App\Mcp\Tools\SearchProductSalesTool::class, [
            'search' => 'Apple',
            'limit' => 5,
        ]);

        dump($response);
        $this->assertIsArray($response);
        $this->assertArrayNotHasKey('error', $response);
    }
}
