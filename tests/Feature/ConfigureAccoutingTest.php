<?php

namespace Tests\Feature;

use App\Classes\Currency;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\Sanctum;
use App\Models\CashFlow;
use App\Models\DashboardDay;
use App\Models\ExpenseCategory;
use App\Services\ReportService;
use App\Services\TestService;
use Tests\TestCase;
use Tests\Traits\WithAccountingTest;
use Tests\Traits\WithAuthentication;

class ConfigureAccoutingTest extends TestCase
{
    use WithAccountingTest, WithAuthentication;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCreateBankingAccounts()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateBankingAccounts();
    }

    public function testCheckSalesTaxes()
    {
        $this->attemptAuthenticate();
        $this->attemptCheckSalesTaxes();
    }
}
