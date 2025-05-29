<?php

namespace App\Classes;

use Illuminate\Support\Facades\Cache as CoreCache;

class Cache extends CoreCache
{
    protected static function applyPrefix( $key )
    {
        return Hook::filter( 'ns-cache-prefix', $key );
    }

    /**
     * Check if a key exists in the cache.
     *
     * @param string $key
     * @return bool
     */
    public static function has( $key )
    {
        $key = self::applyPrefix( $key );

        return parent::has( $key );
    }

    /**
     * Check if a key is missing from the cache.
     *
     * @param string $key
     * @return bool
     */
    public static function missing( $key )
    {
        $key = self::applyPrefix( $key );

        return parent::missing( $key );
    }

    /**
     * Retrieve a value from the cache.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get( $key, $default = null )
    {
        $key = self::applyPrefix( $key );

        return parent::get( $key, $default );
    }

    /**
     * Retrieve multiple values from the cache.
     *
     * @param array $keys
     * @param mixed $default
     * @return array
     */
    public static function getMultiple( $keys, $default = null )
    {
        $keys = array_map( [self::class, 'applyPrefix'], $keys );

        return parent::getMultiple( $keys, $default );
    }

    /**
     * Retrieve and delete a value from the cache.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function pull( $key, $default = null )
    {
        $key = self::applyPrefix( $key );

        return parent::pull( $key, $default );
    }

    /**
     * Store a value in the cache for a given time-to-live (TTL).
     *
     * @param string $key
     * @param mixed $value
     * @param int|null $ttl
     * @return bool
     */
    public static function put( $key, $value, $ttl = null )
    {
        $key = self::applyPrefix( $key );

        return parent::put  ( $key, $value, $ttl );
    }

    public static function set( $key, $value, $ttl = null )
    {
        $key = self::applyPrefix( $key );

        return parent::set( $key, $value, $ttl );
    }

    /**
     * Store multiple key-value pairs in the cache for a given time-to-live (TTL).
     *
     * @param array $values
     * @param int|null $ttl
     * @return bool
     */
    public static function putMany( array $values, $ttl = null )
    {
        $prefixedValues = [];
        foreach ( $values as $key => $value ) {
            $prefixedValues[self::applyPrefix( $key )] = $value;
        }

        return parent::putMany( $prefixedValues, $ttl );
    }

    /**
     * Store multiple key-value pairs in the cache indefinitely.
     *
     * @param array $values
     * @param int|null $ttl
     * @return bool
     */
    public static function setMultiple( $values, $ttl = null )
    {
        $prefixedValues = [];
        foreach ( $values as $key => $value ) {
            $prefixedValues[self::applyPrefix( $key )] = $value;
        }

        return parent::setMultiple( $prefixedValues, $ttl );
    }

    /**
     * Add a value to the cache if it does not already exist.
     *
     * @param string $key
     * @param mixed $value
     * @param int|null $ttl
     * @return bool
     */
    public static function add( $key, $value, $ttl = null )
    {
        $key = self::applyPrefix( $key );

        return parent::add( $key, $value, $ttl );
    }

    /**
     * Increment a value in the cache.
     *
     * @param string $key
     * @param int $value
     * @return int|bool
     */
    public static function increment( $key, $value = 1 )
    {
        $key = self::applyPrefix( $key );

        return parent::increment( $key, $value );
    }

    /**
     * Decrement a value in the cache.
     *
     * @param string $key
     * @param int $value
     * @return int|bool
     */
    public static function decrement( $key, $value = 1 )
    {
        $key = self::applyPrefix( $key );

        return parent::decrement( $key, $value );
    }

    /**
     * Store a value in the cache indefinitely.
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public static function forever( $key, $value )
    {
        $key = self::applyPrefix( $key );

        return parent::forever( $key, $value );
    }

    /**
     * Remember a value in the cache for a given time-to-live (TTL).
     *
     * @param string $key
     * @param int $ttl
     * @param callable $callback
     * @return mixed
     */
    public static function remember( $key, $ttl, $callback )
    {
        $key = self::applyPrefix( $key );

        return parent::remember( $key, $ttl, $callback );
    }

    /**
     * Remember a value in the cache indefinitely.
     *
     * @param string $key
     * @param callable $callback
     * @return mixed
     */
    public static function rememberForever( $key, $callback )
    {
        $key = self::applyPrefix( $key );

        return parent::rememberForever( $key, $callback );
    }

    /**
     * Forget a value in the cache.
     *
     * @param string $key
     * @return bool
     */
    public static function forget( $key )
    {
        $key = self::applyPrefix( $key );

        return parent::forget( $key );
    }

    /**
     * Delete a value from the cache.
     *
     * @param string $key
     * @return bool
     */
    public static function delete( $key )
    {
        $key = self::applyPrefix( $key );

        return parent::delete( $key );
    }

    /**
     * Delete multiple values from the cache.
     *
     * @param array $keys
     * @return bool
     */
    public static function deleteMultiple( $keys )
    {
        $keys = array_map( [self::class, 'applyPrefix'], $keys );

        return parent::deleteMultiple( $keys );
    }
}
