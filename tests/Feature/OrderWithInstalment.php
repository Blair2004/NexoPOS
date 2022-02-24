<?php

namespace Tests\Feature;

use App\Classes\Currency;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderInstalment;
use App\Models\OrderPayment;
use App\Models\Product;
use App\Models\Role;
use App\Services\CurrencyService;
use App\Services\OrdersService;
use Carbon\Carbon;
use Exception;
use Faker\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithOrderTest;

class OrderWithInstalment extends TestCase
{
    use WithAuthentication, WithOrderTest;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCreateOrderWithInstalment()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateOrderWithInstalment();
    }
}
