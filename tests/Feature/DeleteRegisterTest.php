<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithCashRegisterTest;

class DeleteRegisterTest extends TestCase
{
    use WithAuthentication, WithCashRegisterTest;

    public $data;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCreateRegister()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateRegister();
    }

    public function testDeleteRegister()
    {
        $this->attemptAuthenticate();
        $this->attemptDeleteRegister();
    }
}
