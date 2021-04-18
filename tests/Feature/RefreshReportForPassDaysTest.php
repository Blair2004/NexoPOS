<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Services\ReportService;

class RefreshReportForPassDaysTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_example()
    {
        $service        =   app()->make( ReportService::class );
        $dates          =   [];
        $startOfWeek    =   ns()->date->clone()->startOfWeek()->subDay();

        for( $i = 0; $i < 7; $i++ ) {
            $today      =   $startOfWeek->addDay()->clone();

            $service->computeDayReport(
                $today->startOfDay()->toDateTimeString(),
                $today->endOfDay()->toDateTimeString()
            );
        }

        $this->assertTrue( true );        
    }
}
