<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Services\ReportService;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithReportTest;

class RefreshReportForPassDaysTest extends TestCase
{
    use WithAuthentication, WithReportTest;

    protected $totalDaysInterval     =   40;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testRefreshReportForPastDays()
    {
        $this->attemptAuthenticate();
        $this->attemptRefreshReportForPastDays();
    }
}
