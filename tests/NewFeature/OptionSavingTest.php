<?php

namespace Tests\NewFeature;

use App\Models\Option;
use Tests\TestCase;
use Tests\Traits\WithAuthentication;

class OptionSavingTest extends TestCase
{
    use WithAuthentication;

    public function test_option_saving()
    {
        $this->attemptAuthenticate();

        ns()->option->set( '_custom_option', 'Hello World' );
        ns()->option->set( '_custom_option', 'Hello World' );

        $this->assertEquals( 1, Option::where( 'key', '_custom_option' )->count(), 'The options saved twice' );

        $this->assertTrue(
            ns()->option->get( '_custom_option' ) === 'Hello World',
            'The option wasn\'t saved'
        );

        /**
         * Step 1: Saving associative array
         */
        $array = [ 'hello' => 'world' ];
        ns()->option->set( '_custom_array', $array );

        $value = ns()->option->get( '_custom_array' );

        $this->assertTrue(
            $value[ 'hello' ] === $array[ 'hello' ],
            'The option with array wasn\'t saved'
        );

        ns()->option->delete( '_custom_option' );

        $this->assertTrue(
            ns()->option->get( '_custom_option' ) === null,
            'The option wasn\'t deleted'
        );

        $array = [ 'hello' => 'me' ];
        ns()->option->set( '_custom_array', $array );

        $value = ns()->option->get( '_custom_array' );

        $this->assertTrue(
            $value[ 'hello' ] === $array[ 'hello' ],
            'The option with array wasn\'t saved'
        );

        /**
         * Step: 2 Saving simple array
         */
        $array = [ 'Hello', 'World', 'GoodMorning' ];
        ns()->option->set( 'new_array', $array );

        $retreived = ns()->option->get( 'new_array', [] );

        $this->assertTrue( is_array( $retreived ), 'Saved option is not an array.' );
        $this->assertTrue( $retreived[0] === $array[0], 'Wrong saved value index.' );
    }
}
