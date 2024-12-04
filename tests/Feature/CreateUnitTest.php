<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithUnitTest;

class CreateUnitTest extends TestCase
{
    use WithAuthentication, WithUnitTest;

    protected $execute = true;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_create_units()
    {
        /**
         * We'll skip the execution from here.
         */
        if ( ! $this->execute ) {
            return $this->assertTrue( true );
        }

        $this->attemptAuthenticate();
        $this->attemptCreateUnit();
    }
}
