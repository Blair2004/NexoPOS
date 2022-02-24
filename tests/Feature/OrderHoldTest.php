<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithOrderTest;

class OrderHoldTest extends TestCase
{
    use WithAuthentication, WithOrderTest;
    
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testPostingOrder()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateHoldOrder();
    }
}
