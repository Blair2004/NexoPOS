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

    public function set( $key, $value )
    {
        if ( preg_match( '/^[a-zA-Z][a-zA-Z0-9_]*$/', $key ) ) {
            if ( is_numeric( $value ) || is_string( $value ) ) {
                if ( strpos( $value, ' ' ) !== false ) {
                    $value = '"' . $value . '"';
                }
            } else {
                $value = '';
            }

            $this->env_file_data[$key] = htmlspecialchars( $value );
        } else {
            throw new Exception( 'Invalid key format' );
        }
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
