<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerAccountHistory;
use App\Models\Role;
use App\Models\User;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithCustomerTest;

class CustomerCreditTest extends TestCase
{
    use WithAuthentication, WithCustomerTest;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testAddCredit()
    {
        $this->attemptAuthenticate();
        $this->attemptCreditCustomerAccount();
    }

    public function testRemoveCredit()
    {
        $this->attemptAuthenticate();
        $this->attemptRemoveCreditCustomerAccount();
    }
}
