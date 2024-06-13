<?php

namespace App\Classes;

use App\Services\CurrencyService;

class Currency
{
    public static function define( $amount )
    {
        return ns()->currency->define( $amount );
    }

    /**
     * Will return a new intance using
     * the default value.
     *
     * @param float $amount
     */
    public static function fresh( $amount ): CurrencyService
    {
        return ns()->currency->fresh( $amount );
    }

    public static function raw( $amount )
    {
        return ns()->currency->define( $amount )->toFloat();
    }
}
