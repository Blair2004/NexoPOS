<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithReportTest;

class RefreshReportForPassDaysTest extends TestCase
{
    use WithAuthentication, WithReportTest;

    protected $totalDaysInterval = 1;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testRefreshReportForPastDays()
    {
        $this->totalDaysInterval = 40;
        $this->attemptAuthenticate();
        $this->attemptRefreshReportForPastDays();
    }
}
