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
            ns()->currency->define(0.001)
                ->subtractBy(0.00093)
                ->getRaw(),
            (float) 0.00007
        );
    }
}
