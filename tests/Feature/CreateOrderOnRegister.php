<?php

namespace Tests\Feature;

use App\Exceptions\NotAllowedException;
use App\Models\Order;
use App\Models\Register;
use App\Models\RegisterHistory;
use App\Models\Role;
use App\Services\CashRegistersService;
use App\Services\TestService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithOrderTest;

class CreateOrderOnRegister extends TestCase
{
    use WithAuthentication, WithOrderTest;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_create_order_on_register()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateOrderOnRegister();
    }

    public function test_update_order_on_register()
    {
        $this->attemptAuthenticate();
        $this->attemptUpdateOrderOnRegister();
    }
}
