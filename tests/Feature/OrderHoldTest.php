<?php

namespace Tests\Feature;

use App\Models\Order;
use Tests\TestCase;
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
    public function test_posting_order()
    {
        $this->attemptAuthenticate();
        $response = $this->attemptCreateHoldOrder();
        $order = Order::find( $response->json( 'data.order.id' ) );
        $this->attemptUpdateHoldOrder( $order );
    }
}
