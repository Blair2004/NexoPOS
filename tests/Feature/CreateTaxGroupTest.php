<?php

namespace Tests\Feature;

use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithTaxTest;

class CreateTaxGroupTest extends TestCase
{
    use WithAuthentication, WithTaxTest;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCreateTaxGroup()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateTaxGroup();
    }
}
