<?php

namespace Tests\Feature;

use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Traits\WithReportTest;

class CanSeeReportsTest extends TestCase
{
    use WithReportTest;

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
        
        $this->attemptSeeReports();
    }
}
