<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\UnitGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithUnitTest;

class CreateUnitTest extends TestCase
{
    use WithAuthentication, WithUnitTest;

    protected $execute  =   true;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCreateUnits()
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
