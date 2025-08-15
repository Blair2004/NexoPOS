<?php

namespace App\Classes;

class Form
{
    public static function fields( ...$args )
    {
        return collect( $args )->filter()->toArray();
    }
}
