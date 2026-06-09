<?php

namespace Tests\Feature\Mcp;

use App\Mcp\Tools\GetCustomerTool;
use App\Mcp\Tools\SearchCustomersTool;
use App\Models\Customer;
use App\Models\CustomerGroup;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Traits\TestsMcpTools;

class CustomersMcpTest extends TestCase
{
    use DatabaseTransactions, TestsMcpTools;

    public function test_get_customer()
    {
        CustomerGroup::factory()->create();
        $customer = Customer::factory()->create();

        $response = $this->runMcpTool(GetCustomerTool::class, [
            'id' => $customer->id,
        ]);

        $this->assertIsArray($response);
        $this->assertArrayNotHasKey('error', $response);
        $this->assertEquals($customer->id, $response['id']);
    }

    public function test_search_customers()
    {
        $response = $this->runMcpTool(SearchCustomersTool::class, [
            'search' => 'Alice',
            'limit' => 5,
        ]);

        $this->assertIsArray($response);
        $this->assertArrayNotHasKey('error', $response);
    }
}
