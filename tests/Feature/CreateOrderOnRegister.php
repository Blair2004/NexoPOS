<?php

namespace Tests\Feature;

use App\Models\OrderPayment;
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
        $this->attemptCreateOrderOnRegister( [
            'payments' => function ( $details ) {
                return [
                    [
                        'value' => $details[ 'subtotal' ] * 2,
                        'identifier' => OrderPayment::PAYMENT_CASH,
                    ],
                ];
            },
        ] );
    }

    public function test_update_order_on_register()
    {
        $this->attemptAuthenticate();
        $this->attemptUpdateOrderOnRegister();
    }
}
