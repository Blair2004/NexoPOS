<?php

namespace Tests\Feature;

use App\Models\Role;
use Illuminate\Support\Facades\Auth;
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
