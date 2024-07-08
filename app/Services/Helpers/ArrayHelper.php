<?php

namespace App\Services\Helpers;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

trait ArrayHelper
{
    /**
     * Return Odds
     *
     * @param array
     * @param string type
     * @return array
     */
    public static function arrayDivide( array $array, string $type = '' )
    {
        if ( $array ) {
            $result = [
                'odd' => [],
                'even' => [],
            ];

            foreach ( $array as $k => $v ) {
                if ( $k % 2 == 0 ) {
                    $result[ 'even' ][] = $v;
                } else {
                    $result[ 'odd' ][] = $v;
                }
            }

            if ( ! empty( $type ) ) {
                return $result[ $type ];
            }

            return $result;
        }

        return [];
    }

    /**
     * Collection to options
     *
     * @param array collection of Eloquent results
     * @return array of options
     */
    public static function toOptions( $collections, $config )
    {
        $result = [];
        if ( $collections ) {
            foreach ( $collections as $collection ) {
                $id = $config[0];
                $name = $config[1];
                $result[ $collection->$id ] = $collection->$name;
            }

            return $result;
        }

        return [];
    }

    /**
     * to JS options
     *
     * @param Collection
     * @param array [ value, label ]
     * @return array of options
     */
    public static function toJsOptions( Collection|EloquentCollection $collections, $config, $defaults = [] ): array
    {
        $result = [];

        /**
         * This will populate defaults
         * value for the options
         */
        if ( ! empty( $defaults ) ) {
            foreach ( $defaults as $value => $label ) {
                $result[] = compact( 'label', 'value' );
            }
        }

        if ( $collections ) {
            foreach ( $collections as $collection ) {

                if ( is_callable( $config ) ) {
                    $result[] = $config( $collection );
                } elseif ( ! is_array( $config[1] ) ) {
                    $id = $config[0];
                    $name = $config[1];

                    $result[] = [
                        'label' => $collection->$name,
                        'value' => $collection->$id,
                    ];
                } else {
                    $name = '';
                    $id = $config[0];

                    foreach ( $config[1] as $index => $_name ) {
                        if ( $index + 1 < count( $config[1] ) ) {
                            $name .= $collection->$_name . ( $config[2] ?? ' ' ); // if separator is not provided
                        } else {
                            $name .= $collection->$_name;
                        }
                    }

                    $result[] = [
                        'label' => $name,
                        'value' => $collection->$id,
                    ];
                }
            }

            return $result;
        }

        return [];
    }

    /**
     * Key Value To Js Options
     *
     * @param array
     * @return array of options
     */
    public static function kvToJsOptions( $array )
    {
        $final = [];
        foreach ( $array as $value => $label ) {
            $final[] = compact( 'label', 'value' );
        }

        return $final;
    }

    /**
     * Key Value To Js Options
     *
     * @param array
     * @return array of options
     */
    public static function boolToOptions( $true, $false )
    {
        return [
            [
                'label' => $true,
                'value' => true,
            ],
            [
                'label' => $false,
                'value' => false,
            ],
        ];
    }

    /**
     * flat multidimensional array using
     * keys
     *
     * @param  array      $data
     * @return Collection
     */
    public static function flatArrayWithKeys( $data )
    {
        return collect( $data )->mapWithKeys( function ( $data, $index ) {
            if ( ! is_array( $data ) || is_numeric( $index ) ) {
                return [ $index => $data ];
            } elseif ( is_array( $data ) ) {
                if ( array_keys( $data ) !== range( 0, count( $data ) - 1 ) ) {
                    return self::flatArrayWithKeys( $data );
                } else {
                    return [ $index => json_encode( $data ) ];
                }
            }

            return [];
        } )->filter( function ( $field ) {
            return $field !== false;
        } );
    }
}
