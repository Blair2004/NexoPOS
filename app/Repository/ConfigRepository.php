<?php

namespace App\Repository;

class ConfigRepository extends Repository
{
    public function __construct()
    {
        $this->items = config()->app();
    }

    public function get( $key, $default = null )
    {
        if ( is_string( $key ) && str_contains( $key, '::' ) ) {
            [$namespace, $path] = explode( '::', $key, 2 );
            $key = "modules.{$namespace}.{$path}";
        }

        return parent::get( $key, $default );
    }
}
