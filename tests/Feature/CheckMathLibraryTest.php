<?php

namespace Tests\Feature;

use Tests\TestCase;

class CheckMathLibraryTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_decimal_precision()
    {
        ns()->option->set( 'ns_currency_precision', 5 );

        $this->assertEquals(
            ns()->currency->define( 0.1 )
                ->additionateBy( 0.2 )
                ->toFloat(),
            (float) 0.3
        );

        $this->assertEquals(
            ns()->currency->define( 0.92 )
                ->dividedBy( 0.3 )
                ->toFloat(),
            (float) 3.066666667
        );

        $this->assertEquals(
            ns()->currency->define( 0.1 )
                ->additionateBy( 0.2 )
                ->multipliedBy( 4 )
                ->toFloat(),
            (float) 1.2
        );

        $this->assertEquals(
            ns()->currency->define( 0.25 )
                ->toFloat(),
            (float) 0.25
        );

        $this->assertEquals(
            ns()->currency->define( 0.001 )
                ->subtractBy( 0.00093 )
                ->toFloat(),
            (float) 0.00007
        );

        ns()->option->set( 'ns_currency_precision', 0 );

        $this->assertEquals(
            (string) ns()->currency->define( 0.2 )
                ->subtractBy( 0.1 ),
            ns()->currency->define( '0' )->format()
        );

        $this->assertEquals(
            (string) ns()->currency->define( 0.6 )
                ->subtractBy( 0.1 ),
            ns()->currency->define( '1' )->format()
        );

        ns()->option->set( 'ns_currency_precision', 0 );

        $this->assertEquals(
            (string) ns()->currency->define( 5.25 )
                ->subtractBy( 3.75 ),
            ns()->currency->define( '2' )->format()
        );

        ns()->option->set( 'ns_currency_precision', 1 );

        $this->assertEquals(
            (string) ns()->currency->define( 5.25 )
                ->subtractBy( 3.75 ),
            ns()->currency->define( '1.5' )->format()
        );
    }
}
