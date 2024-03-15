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
        return $this->attemptUpdateRegister( $register );
    }

    /**
     * @depends testUpdateRegister
     */
    public function testDeleteRegister( Register $register )
    {
        $this->attemptAuthenticate();
        return $this->attemptDeleteRegister( $register );
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
    }

    /**
     * @depends testCashInRegister
     */
    public function testCashOutRegister( $register )
    {
        $this->attemptAuthenticate();
        $this->attemptCashOutRegister( $register );
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
