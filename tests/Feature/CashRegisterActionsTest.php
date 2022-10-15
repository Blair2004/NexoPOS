<?php

namespace Tests\Feature;

use App\Models\Role;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Traits\WithCashRegisterTest;

class CashRegisterActionsTest extends TestCase
{
    use WithCashRegisterTest;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCreateCashRegisterWithActions()
    {
        Sanctum::actingAs(
            Role::namespace( 'admin' )->users->first(),
            ['*']
        );

        $this->attemptCreateCashRegisterWithActions();
    }
}
