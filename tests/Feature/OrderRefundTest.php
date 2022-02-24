<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\ExpenseCategory;
use App\Models\OrderPayment;
use App\Models\OrderProductRefund;
use App\Models\Product;
use App\Models\Role;
use App\Models\TaxGroup;
use App\Services\CurrencyService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithOrderTest;

class OrderRefundTest extends TestCase
{
    use WithOrderTest, WithAuthentication;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testRefund()
    {
        $this->attemptAuthenticate();
        $this->attemptRefundOrder();
    }
}
