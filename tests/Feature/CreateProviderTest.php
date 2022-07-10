<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithProviderTest;

class CreateProviderTest extends TestCase
{
    use WithAuthentication, WithProviderTest;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCreateProvider()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateProvider();
    }
}
