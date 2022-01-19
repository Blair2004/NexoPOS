<?php

namespace Tests\Feature;

use App\Classes\Currency;
use App\Models\AccountType;
use App\Models\CashFlow;
use App\Models\Customer;
use App\Models\CustomerCoupon;
use App\Models\OrderPayment;
use App\Models\OrderProductRefund;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\CurrencyService;
use App\Services\TaxService;
use Exception;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Arr;
use Tests\TestCase;
use Faker\Factory;
use Illuminate\Support\Facades\Event;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithOrderTest;

class CreateOrderTest extends TestCase
{
    use WithAuthentication, WithOrderTest;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testPostingOrder( $callback = null )
    {
        if ( $this->defaultProcessing ) {
            $this->attemptAuthenticate();
    
            return $this->attemptPostOrder( $callback );
        } else {
            $this->assertTrue( true ); // because we haven't performed any test.
        }
    }
}
