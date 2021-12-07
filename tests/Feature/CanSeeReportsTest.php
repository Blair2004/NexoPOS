<?php

namespace Tests\Feature;

use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CanSeeReportsTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_canSeeReports()
    {
        Auth::loginUsingId( 
            Role::namespace( 'admin' )->users->first()->id 
        );
        
        $reports    =   [
            '/dashboard/reports/sales',
            '/dashboard/reports/sales-progress',
            '/dashboard/reports/low-stock',
            '/dashboard/reports/sold-stock',
            '/dashboard/reports/profit',
            '/dashboard/reports/cash-flow',
            '/dashboard/reports/annual-report',
            '/dashboard/reports/payment-types',
        ];

        foreach( $reports as $report ) {
            $response       =   $this->withSession( $this->app[ 'session' ]->all() )
                ->json( 'GET', $report );

            $response->assertStatus(200);
        }

    }
}
