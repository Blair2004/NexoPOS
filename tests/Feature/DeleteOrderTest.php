<?php

namespace Tests\Feature;

use App\Models\CashFlow;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\OrderProduct;
use App\Models\Product;
use App\Models\Role;
use App\Services\OrdersService;
use App\Services\ProductService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithOrderTest;

class DeleteOrderTest extends CreateOrderTest
{
    use WithAuthentication, WithOrderTest;

    protected $count                =   1;
    protected $totalDaysInterval    =   1;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_delete_order()
    {
        $this->attemptAuthenticate();
        $this->attemptDeleteOrder();

    }
}
