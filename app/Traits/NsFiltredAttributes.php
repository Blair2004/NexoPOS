<?php

namespace App\Traits;

use App\Classes\Hook;

trait NsFiltredAttributes
{
    public function filterAttribute( string $attribute, array $data ): string|float|int
    {
        return Hook::filter( get_called_class() . '::' . $attribute, $this->{$attribute}, $data );
    }

    public static function attributeFilter( string $attribute, $callback )
    {
        return Hook::addFilter( get_called_class() . '::' . $attribute, $callback, 10, 2 );
    }
}
