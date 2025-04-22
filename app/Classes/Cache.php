<?php

namespace App\Classes;

use Illuminate\Support\Facades\Cache as CoreCache;

class Cache extends CoreCache
{
    protected static function applyPrefix( $key )
    {
        return Hook::filter( 'ns-cache-prefix', $key );
    }

    public static function has( $key )
    {
        $key = self::applyPrefix( $key );

        return parent::has( $key );
    }

    public static function missing( $key )
    {
        $key = self::applyPrefix( $key );

        return parent::missing( $key );
    }

    public static function get( $key, $default = null )
    {
        $key = self::applyPrefix( $key );

        return parent::get( $key, $default );
    }

    public static function getMultiple( $keys, $default = null )
    {
        $keys = array_map( [self::class, 'applyPrefix'], $keys );

        return parent::getMultiple( $keys, $default );
    }

    public static function pull( $key, $default = null )
    {
        $key = self::applyPrefix( $key );

        return parent::pull( $key, $default );
    }

    public static function put( $key, $value, $ttl = null )
    {
        $key = self::applyPrefix( $key );

        return parent::put( $key, $value, $ttl );
    }

    public static function set( $key, $value, $ttl = null )
    {
        $key = self::applyPrefix( $key );

        return parent::set( $key, $value, $ttl );
    }

    public static function putMany( array $values, $ttl = null )
    {
        $prefixedValues = [];
        foreach ( $values as $key => $value ) {
            $prefixedValues[self::applyPrefix( $key )] = $value;
        }

        return parent::putMany( $prefixedValues, $ttl );
    }

    public static function setMultiple( $values, $ttl = null )
    {
        $prefixedValues = [];
        foreach ( $values as $key => $value ) {
            $prefixedValues[self::applyPrefix( $key )] = $value;
        }

        return parent::setMultiple( $prefixedValues, $ttl );
    }

    public static function add( $key, $value, $ttl = null )
    {
        $key = self::applyPrefix( $key );

        return parent::add( $key, $value, $ttl );
    }

    public static function increment( $key, $value = 1 )
    {
        $key = self::applyPrefix( $key );

        return parent::increment( $key, $value );
    }

    public static function decrement( $key, $value = 1 )
    {
        $key = self::applyPrefix( $key );

        return parent::decrement( $key, $value );
    }

    public static function forever( $key, $value )
    {
        $key = self::applyPrefix( $key );

        return parent::forever( $key, $value );
    }

    public static function remember( $key, $ttl, $callback )
    {
        $key = self::applyPrefix( $key );

        return parent::remember( $key, $ttl, $callback );
    }

    public static function rememberForever( $key, $callback )
    {
        $key = self::applyPrefix( $key );

        return parent::rememberForever( $key, $callback );
    }

    public static function forget( $key )
    {
        $key = self::applyPrefix( $key );

        return parent::forget( $key );
    }

    public static function delete( $key )
    {
        $key = self::applyPrefix( $key );

        return parent::delete( $key );
    }

    public static function deleteMultiple( $keys )
    {
        $keys = array_map( [self::class, 'applyPrefix'], $keys );

        return parent::deleteMultiple( $keys );
    }
}
