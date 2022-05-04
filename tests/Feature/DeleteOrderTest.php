<?php

namespace Tests\Feature;

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
