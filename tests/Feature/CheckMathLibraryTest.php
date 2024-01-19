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
        ns()->option->set('ns_currency_precision', 5);

        $this->assertEquals(
            ns()->currency->define(0.1)
                ->additionateBy(0.2)
                ->getRaw(),
            (float) 0.3
        );

        $this->assertEquals(
            ns()->currency->define(0.1)
                ->additionateBy(0.2)
                ->multipliedBy(4)
                ->getRaw(),
            (float) 1.2
        );

        $this->assertEquals(
            ns()->currency->define(0.25)
                ->getRaw(),
            (float) 0.25
        );

        $this->assertEquals(
            ns()->currency->define(0.001)
                ->subtractBy(0.00093)
                ->getRaw(),
            (float) 0.00007
        );

        ns()->option->set('ns_currency_precision', 0);

        $this->assertEquals(
            ns()->currency->define(0.2)
                ->subtractBy(0.1)
                ->getRaw(),
            (float) 1
        );

        $this->assertEquals(
            ns()->currency->define(5.25)
                ->subtractBy(3.75)
                ->getRaw(),
            (float) 2
        );
    }
}
