<?php

namespace Tests\Feature;

use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithProviderTest;

class CreateProviderTest extends TestCase
{
    use WithAuthentication, WithProviderTest;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCreateProvider()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateProvider();        
    }
}
