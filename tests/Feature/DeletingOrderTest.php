<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithOrderTest;

class DeletingOrderTest extends TestCase
{
    use WithAuthentication, WithOrderTest;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testDeleteOrder()
    {
        $this->attemptAuthenticate();
        $this->attemptDeleteOrder();
    }
}
