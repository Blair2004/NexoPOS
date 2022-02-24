<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerAccountHistory;
use App\Models\CustomerGroup;
use App\Models\Role;
use App\Services\CustomerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Faker\Factory;
use Illuminate\Support\Facades\Auth;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithCustomerTest;

class CreateCustomerTest extends TestCase
{
    use WithAuthentication, WithCustomerTest;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testExample()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateCustomer();
    }
}
