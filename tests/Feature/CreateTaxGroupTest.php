<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithTaxTest;

class CreateTaxGroupTest extends TestCase
{
    use WithAuthentication, WithTaxTest;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_create_tax_group()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateTaxGroup();
    }
}
