<?php

namespace Tests\Feature;

use App\Models\Register;
use Tests\TestCase;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithCashRegisterTest;

class CreateRegisterTest extends TestCase
{
    use WithAuthentication, WithCashRegisterTest;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_create_register()
    {
        $this->attemptAuthenticate();

        return $this->attemptCreateRegisterTransactions();
    }

    /**
     * @depends test_create_register
     */
    public function test_update_register( Register $register )
    {
        $this->attemptAuthenticate();
        $this->attemptUpdateRegister( $register );

        return $register;
    }

    /**
     * @depends test_update_register
     */
    public function test_delete_register( Register $register )
    {
        $this->attemptAuthenticate();
        $this->attemptDeleteRegister( $register );

        return $register;
    }

    public function test_open_register()
    {
        $this->attemptAuthenticate();
        $register = $this->attemptCreateRegister();
        $this->attemptOpenRegister( $register );

        return $register;
    }

    /**
     * @depends test_open_register
     */
    public function test_cash_in_register( Register $register )
    {
        $this->attemptAuthenticate();
        $this->attemptCashInRegister( $register );

        return $register;
    }

    /**
     * @depends test_cash_in_register
     */
    public function test_cash_out_register( $register )
    {
        $this->attemptAuthenticate();
        $this->attemptCashOutRegister( $register );

        return $register;
    }

    /**
     * @depends test_cash_out_register
     */
    public function test_close_register( $register )
    {
        $this->attemptAuthenticate();
        $this->attemptCloseRegister( $register );
    }
}
