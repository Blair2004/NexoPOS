<?php

namespace Tests\Feature;

use App\Classes\Currency;
use App\Models\CashFlow;
use App\Models\Procurement;
use App\Models\Product;
use App\Models\Provider;
use App\Models\Role;
use App\Models\TaxGroup;
use App\Services\CurrencyService;
use App\Services\TaxService;
use App\Services\TestService;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Tests\TestCase;
use Faker\Factory;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithProcurementTest;

class MakeProcurementTest extends TestCase
{
    use WithAuthentication, WithProcurementTest;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCreateProcurement()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateProcurement();
    }
}
