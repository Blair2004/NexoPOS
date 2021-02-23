<?php
namespace App\Classes;

class Currency 
{
    public static function define( $amount )
    {
        return ns()->currency->define( $amount );
    }

    public static function raw( $amount )
    {
        return ns()->currency->getRaw( $amount );
    }
}