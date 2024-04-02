<?php

namespace Tests\Feature;

use Tests\TestCase;

class HandlePermissionsManagement extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_example()
    {
        $response = $this->get( '/' );

        $response->assertStatus( 200 );
    }
}
