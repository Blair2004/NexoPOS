<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\PaymentType;
use App\Models\Product;
use App\Models\Role;
use App\Services\CurrencyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithOrderTest;

class CreateCustomPaymentTypeAndOrder extends TestCase
{
    use WithAuthentication, WithOrderTest;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_create_payment_type_and_order()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateCustomPaymentType();
        $this->attemptCreateCustomerOrder();
    }
}
