<?php

namespace App\Services;

use Brick\Math\BigNumber;

class MathService
{
    public function set( $value )
    {
        return BigNumber::of( $value );
    }
}
