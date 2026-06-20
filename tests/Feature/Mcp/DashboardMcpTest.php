<?php

namespace Tests\Feature\Mcp;

use App\Mcp\Tools\GetDashboardSummaryTool;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Traits\TestsMcpTools;

class DashboardMcpTest extends TestCase
{
    use DatabaseTransactions, TestsMcpTools;

    public function test_get_dashboard_summary()
    {
        $response = $this->runMcpTool( GetDashboardSummaryTool::class, [] );

        $this->assertIsArray( $response );
        $this->assertArrayNotHasKey( 'error', $response );
        $this->assertArrayHasKey( 'day_paid_orders', $response );
        $this->assertArrayHasKey( 'total_income', $response );
    }
}
