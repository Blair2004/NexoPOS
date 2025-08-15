<?php

namespace App\Classes;

/**
 * Class Config
 *
 * This class provides a wrapper around the Laravel configuration system,
 * allowing for easier management of configuration values, including support
 * for module-specific configurations using the "namespace::path" syntax.
 */
class Config
{
    /**
     * Retrieve a configuration value.
     *
     * @param  string     $key     The configuration key, optionally in "namespace::path" format.
     * @param  mixed|null $default The default value to return if the key does not exist.
     * @return mixed      The configuration value or the default value.
     */
    public function get( string $key, $default = null )
    {
        if ( is_string( $key ) && str_contains( $key, '::' ) ) {
            [ $namespace, $path ] = explode( '::', $key, 2 );
            $key = "modules.{$namespace}.{$path}";
        }

        return config( $key, $default );
    }

    /**
     * Set a configuration value.
     *
     * @param  string $key   The configuration key, optionally in "namespace::path" format.
     * @param  mixed  $value The value to set for the configuration key.
     * @return bool   True if the configuration was set successfully, false otherwise.
     */
    public function set( string $key, $value ): bool
    {
        if ( is_string( $key ) && str_contains( $key, '::' ) ) {
            [ $namespace, $path ] = explode( '::', $key, 2 );
            $key = "modules.{$namespace}.{$path}";
        }

        return config( $key, $value );
    }

    /**
     * Check if a configuration key exists.
     *
     * @param  string $key The configuration key, optionally in "namespace::path" format.
     * @return bool   True if the configuration key exists, false otherwise.
     */
    public function has( string $key ): bool
    {
        if ( is_string( $key ) && str_contains( $key, '::' ) ) {
            [ $namespace, $path ] = explode( '::', $key, 2 );
            $key = "modules.{$namespace}.{$path}";
        }

        return config()->has( $key );
    }

    /**
     * Retrieve all configuration values.
     *
     * @return array An associative array of all configuration values.
     */
    public function all(): array
    {
        return config()->all();
    }

    /**
     * Set multiple configuration values.
     *
     * @param array $config An associative array of configuration keys and values.
     */
    public function setAll( array $config ): void
    {
        config()->set( $config );
    }

    /**
     * Remove a configuration key.
     *
     * @param  string $key The configuration key, optionally in "namespace::path" format.
     * @return bool   True if the configuration key was removed successfully, false otherwise.
     */
    public function forget( string $key ): bool
    {
        if ( is_string( $key ) && str_contains( $key, '::' ) ) {
            [ $namespace, $path ] = explode( '::', $key, 2 );
            $key = "modules.{$namespace}.{$path}";
        }

        return config()->forget( $key );
    }

    /**
     * Clear all configuration values.
     */
    public function clear(): void
    {
        config()->clear();
    }
}
