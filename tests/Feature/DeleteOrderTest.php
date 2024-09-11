<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithOrderTest;

class DeleteOrderTest extends TestCase
{
    use WithAuthentication, WithOrderTest;

    protected $count = 1;

    protected $totalDaysInterval = 1;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_delete_order()
    {
        $this->attemptAuthenticate();
        $this->attemptTestDeleteOrder();
    }

    public function test_void_order()
    {
        $this->attemptAuthenticate();
        $this->attemptTestVoidOrder();
    }
}
