<?php

namespace Tests\Feature;

use App\Exceptions\NotAllowedException;
use App\Models\CashFlow;
use App\Models\Register;
use App\Models\RegisterHistory;
use App\Models\Role;
use App\Services\CashRegistersService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithCashRegisterTest;

class CreateRegisterTest extends TestCase
{
    use WithAuthentication, WithCashRegisterTest;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCreateRegister()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateRegisterTransactions();
    }
}
