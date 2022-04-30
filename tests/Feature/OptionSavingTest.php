<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tests\Traits\WithAuthentication;

class OptionSavingTest extends TestCase
{
    use WithFaker, WithAuthentication;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_save_and_retreive_options()
    {
        $this->attemptAuthenticate();

        ns()->option->set( '_custom_option', 'Hello World' );

        $this->assertTrue( 
            ns()->option->get( '_custom_option' ) === 'Hello World',
            'The option wasn\'t saved'
        );

        $array      =   [ 'hello'   =>  'world' ];
        ns()->option->set( '_custom_array', $array );

        $value      =   ns()->option->get( '_custom_array' );

        $this->assertTrue( 
            $value[ 'hello' ] === 'world',
            'The option with array wasn\'t saved'
        );

        ns()->option->delete( '_custom_option' );

        $this->assertTrue( 
            ns()->option->get( '_custom_option' ) === null,
            'The option wasn\'t deleted'
        );
    }
}
