<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Services\ReportService;

class RefreshReportForPassDaysTest extends TestCase
{
    protected $totalDaysInterval     =   40;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_example()
    {
        $service        =   app()->make( ReportService::class );
        $startInterval  =   ns()->date->clone()->subDays( $this->totalDaysInterval )->subDay();

        for( $i = 0; $i < $this->totalDaysInterval; $i++ ) {
            $today      =   $startInterval->addDay()->clone();

            $service->computeDayReport(
                $today->startOfDay()->toDateTimeString(),
                $today->endOfDay()->toDateTimeString()
            );
        }

        $this->assertTrue( true );        
    }
}
