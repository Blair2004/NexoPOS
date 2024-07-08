<?php

namespace Tests\Feature;

use App\Services\DateService;
use Tests\TestCase;
use Tests\Traits\WithAuthentication;

class TestAllDateTimeOptions extends TestCase
{
    use WithAuthentication;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_check_timezone_validity()
    {
        $this->attemptAuthenticate();

        $timezones = config( 'nexopos.timezones' );

        foreach ( $timezones as $zone => $name ) {
            $this->assertTrue( new DateService( 'now', $zone ) instanceof DateService );
        }
    }

    public function test_compare_dates()
    {
        $this->assertSame(
            now()->toFormattedDateString( 'Y-m-d H:m' ),
            ns()->date->toFormattedDateString( 'Y-m-d H:m' )
        );
    }
}
