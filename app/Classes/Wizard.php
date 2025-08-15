<?php

namespace App\Classes;

class Wizard
{
    public static function steps( array $steps )
    {
        return $steps;
    }

    public static function step( string $title, string $description, array $fields, string $component = '', bool $completed = false )
    {
        return compact( 'title', 'description', 'component' );
    }

    public static function fields( ...$args )
    {
        return collect( $args )->filter()->toArray();
    }
}
