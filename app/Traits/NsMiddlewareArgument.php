<?php
namespace App\Traits;

trait NsMiddlewareArgument
{
    public static function arguments( string $arguments )
    {
        return self::class . ':' . $arguments;
    }
}