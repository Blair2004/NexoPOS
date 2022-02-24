<?php

namespace Tests\Feature;

use App\Models\RewardSystem;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithCustomerTest;

class CreateCustomerGroupTest extends TestCase
{
    use WithAuthentication, WithCustomerTest;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCreateCustomerGroup()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateCustomerGroup();        
    }
}
