<?php

namespace App\Services;

use App\Models\Option;

class Options
{
    private $rawOptions = [];

    private $options = [];

    private $isUserOptions = false;

    private $option;

    private $user_id;

    private $value;

    private $hasFound;

    private $removableIndex;

    public string $tableName;

    /**
     * the option class can be constructed with the user id.
     * If the user is not provided, the general options are loaded instead.
     */
    public function __construct()
    {
        $this->tableName = ( new Option )->getTable();
        $this->build();
    }

    /**
     * Will reset the default options
     *
     * @param array $options
     */
    public function setDefault( $options = [] ): void
    {
        Option::truncate();

        $types = app()->make( OrdersService::class )->getTypeLabels();

        $defaultOptions = [
            'ns_registration_enabled' => false,
            'ns_store_name' => 'NexoPOS 4.x',
            'ns_pos_order_types' => array_keys( $types ),
        ];

        $options = array_merge( $defaultOptions, $options );

        foreach ( $options as $key => $value ) {
            $this->set( $key, $value );
        }
    }

    /**
     * return option service
     *
     * @return object
     */
    public function option()
    {
        return Option::where( 'user_id', null );
    }

    /**
     * Build
     * Build option array
     *
     * @return void
     **/
    public function build()
    {
        $this->options = [];

        if ( Helper::installed() && empty( $this->rawOptions ) ) {
            $this->rawOptions = $this->option()
                ->get()
                ->mapWithKeys( function ( $option ) {
                    return [
                        $option->key => $option,
                    ];
                });
        }
    }

    /**
     * Set Option
     *
     * @param string key
     * @param any value
     * @param bool force set
     * @return void
     **/
    public function set( $key, $value, $expiration = null )
    {
        /**
         * We rather like to remove unecessary spaces. That might
         * cause unwanted behaviors.
         */
        $key = trim( strtolower( $key ) );

        /**
         * if an option has been found,
         * it will save the new value and update
         * the option object.
         */
        $foundOption = collect( $this->rawOptions )->filter( fn( $option, $index ) => $index === $key );

        /**
         * if the option hasn't been found
         * it will create a new Option model
         * and store with, then save it on the option model
         */
        if ( $foundOption->isEmpty() ) {
            $option = new Option;
        } else {
            $option = $foundOption->first();
        }

        $option->key = $key;
        $option->array = false;

        if ( is_array( $value ) ) {
            $option->value = json_encode( $value );
        } elseif ( empty( $value ) && ! (bool) preg_match( '/[0-9]{1,}/', $value ) ) {
            $option->value = '';
        } else {
            $option->value = $value;
        }

        $option->expire_on = $expiration;

        /**
         * this should be overridable
         * from a user option or any
         * extending this class
         */
        $option = $this->beforeSave( $option );
        $option->save();

        /**
         * Let's save the new option
         */
        $this->rawOptions[ $key ] = $option;

        return $option;
    }

    public function beforeSave( $option )
    {
        /**
         * sanitizing input to remove
         * all script tags
         */
        $option->value = strip_tags( $option->value );

        return $option;
    }

    /**
     * Get options
     *
     * @param string|array $key
     * @return string|array|bool|float
     **/
    public function get( $key = null, $default = null )
    {
        if ( $key === null ) {
            return $this->rawOptions;
        }

        $filtredOptions = collect( $this->rawOptions )->filter( function ( $option ) use ( $key  ) {
            return is_array( $key ) ? in_array( $option->key, $key ) : $option->key === $key;
        });

        $options = $filtredOptions->map( function ( $option ) {
            /**
             * We should'nt run this everytime we
             * try to pull an option from the database or from the array
             */
            if ( ! empty( $option->value ) && ! $option->parsed ) {
                if ( is_string( $option->value ) ) {
                    $json = json_decode( $option->value, true );

                    if ( json_last_error() == JSON_ERROR_NONE ) {
                        $option->value = $json;
                        $option->parsed = true;
                    }
                } elseif ( ! is_array( $option->value ) ) {
                    $option->parsed = true;
                    $option->value = match ( $option->value ) {
                        preg_match( '/[0-9]{1,}/', $option->value ) => (int) $option->value,
                        preg_match( '/[0-9]{1,}\.[0-9]{1,}/', $option->value ) => (float) $option->value,
                        default => $option->value,
                    };
                }
            }

            return $option;
        });

        return match ( $options->count() ) {
            0 => $default,
            1 => $options->first()->value,
            default => $options->map( fn( $option ) => $option->value )->toArray()
        };
    }

    /**
     * Delete Key
     *
     * @param string key
     **/
    public function delete( $key ): void
    {
        $this->rawOptions = collect( $this->rawOptions )->filter( function ( Option $option ) use ( $key ) {
            if ( $option->key === $key ) {
                $option->delete();

                return false;
            }

            return true;
        });
    }
}
