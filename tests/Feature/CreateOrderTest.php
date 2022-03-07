<?php

namespace Tests\Feature;

use App\Models\Order;
use Tests\TestCase;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithOrderTest;

class CreateOrderTest extends TestCase
{
    use WithAuthentication, WithOrderTest;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testPostingOrder( $callback = null )
    {
        if ( $this->defaultProcessing ) {
            $this->attemptAuthenticate();
    
            return $this->attemptPostOrder( $callback );
        } else {
            $this->assertTrue( true ); // because we haven't performed any test.
        }
    }

    public function testCreateOrderWithNoPayment( $callback = null )
    {
        if ( $this->defaultProcessing ) {
            $this->attemptAuthenticate();
    
            $this->count                =   1;
            $this->totalDaysInterval    =   1;
            $this->processCoupon        =   false;
            $this->useDiscount          =   false;
            $this->shouldMakePayment    =   false;
            $this->customOrderParams    =   [
                'shipping'  =>  0,
            ];
            $this->customProductParams  =   [
                'unit_price'    =>  0,
                'discount'      =>  0,
            ];

            $responses  =   $this->attemptPostOrder( $callback );

            $this->assertEquals( Order::PAYMENT_PAID, $responses[0][0][ 'order-creation' ][ 'data' ][ 'order' ][ 'payment_status' ]);

        } else {
            $this->assertTrue( true ); // because we haven't performed any test.
        }
    }
}
