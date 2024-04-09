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
    public function testCreateRegister()
    {
        $this->attemptAuthenticate();

        return $this->attemptCreateRegisterTransactions();
    }

    /**
     * @depends testCreateRegister
     */
    public function testUpdateRegister( Register $register )
    {
        $this->attemptAuthenticate();
        $this->attemptUpdateRegister( $register );

        return $register;
    }

    /**
     * @depends testUpdateRegister
     */
    public function testDeleteRegister( Register $register )
    {
        $this->attemptAuthenticate();
        $this->attemptDeleteRegister( $register );

        return $register;
    }

    public function testOpenRegister()
    {
        $this->attemptAuthenticate();
        $register = $this->attemptCreateRegister();
        $this->attemptOpenRegister( $register );

        return $register;
    }

    /**
     * @depends testOpenRegister
     */
    public function testCashInRegister( Register $register )
    {
        $this->attemptAuthenticate();
        $this->attemptCashInRegister( $register );

        return $register;
    }

    /**
     * @depends testCashInRegister
     */
    public function testCashOutRegister( $register )
    {
        $this->attemptAuthenticate();
        $this->attemptCashOutRegister( $register );

        return $register;
    }

    /**
     * @depends testCashOutRegister
     */
    public function testCloseRegister( $register )
    {
        $this->attemptAuthenticate();
        $this->attemptCloseRegister( $register );
    }
}
