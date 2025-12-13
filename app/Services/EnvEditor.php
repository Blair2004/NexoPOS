<?php

namespace App\Services;

use Exception;

class EnvEditor
{
    private $env_file_path;

    private $env_file_data;

    public function __construct( $env_file_path )
    {
        $this->env_file_path = $env_file_path;
        $this->env_file_data = $this->read( $this->env_file_path );
    }

    public function read( $filePath )
    {
        $result = [];

        if ( is_file( $filePath ) === false ) {
            return $result;
        }

        $file = fopen( $filePath, 'r' );
        if ( $file ) {
            while ( ( $line = fgets( $file ) ) !== false ) {
                if ( substr( $line, 0, 1 ) === '#' || trim( $line ) === '' ) {
                    continue;
                }
                $parts = explode( '=', $line );
                $key = trim( $parts[0] );
                $value = isset( $parts[1] ) ? trim( $parts[1] ) : null;
                $result[$key] = $value;
            }
            fclose( $file );
        }

        return $result;
    }

    public function get( $key, $default = null )
    {
        return array_key_exists( $key, $this->env_file_data ) ? $this->env_file_data[$key] : $default;
    }

    public function delete( $key )
    {
        unset( $this->env_file_data[$key] );
        $this->write();
    }

    public function set( $key, $value, $quoted = true )
    {
        // Validate key format
        if ( !preg_match( '/^[a-zA-Z][a-zA-Z0-9_]*$/', $key ) ) {
            throw new Exception( 'Invalid key format' );
        }

        // Sanitize value
        if ( is_numeric( $value ) || is_string( $value ) ) {
            // Remove any newline characters to prevent injection
            $value = str_replace( ["\r", "\n"], '', (string)$value );
            
            // Escape backslashes and double quotes for .env context
            $value = addslashes( $value );
            
            // Quote string values based on $quoted parameter
            if ( $quoted && is_string( $value ) && !is_numeric( $value ) ) {
                $value = '"' . $value . '"';
            }
        } else {
            $value = '""'; // Empty quoted string for safety
        }

        $this->env_file_data[$key] = $value;
        $this->write();
    }

    public function has( $key )
    {
        return array_key_exists( $key, $this->env_file_data );
    }

    private function write()
    {
        file_put_contents(
            $this->env_file_path,
            implode( "\n", array_map(
                function ( $v, $k ) {
                    return "$k=$v";
                },
                $this->env_file_data,
                array_keys( $this->env_file_data )
            ) )
        );
    }
}
