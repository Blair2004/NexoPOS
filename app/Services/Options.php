<?php

namespace App\Services;

use App\Models\Option;

class Options
{
    private $rawOptions = [];

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

        $defaultOptions = [
            'ns_registration_enabled' => 'no',
            'ns_store_name' => 'NexoPOS',
            'ns_pos_allow_decimal_quantities' => 'yes',
            'ns_pos_quick_product' => 'yes',
            'ns_pos_show_quantity' => 'yes',
            'ns_currency_precision' => 2,
            'ns_pos_hide_empty_categories' => 'yes',
            'ns_pos_unit_price_ediable' => 'yes',
            'ns_pos_order_types' => [ 'takeaway', 'delivery' ],
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
                } );
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
         * if an option has been found,
         * it will save the new value and update
         * the option object.
         */
        $foundOption = collect( $this->rawOptions )->map( function ( $option, $index ) use ( $value, $key, $expiration ) {
            if ( $key === $index ) {
                $this->hasFound = true;

                $this->encodeOptionValue( $option, $value );

                $option->expire_on = $expiration;

                /**
                 * this should be overridable
                 * from a user option or any
                 * extending this class
                 */
                $option = $this->beforeSave( $option );
                $option->save();

                return $option;
            }

            return false;
        } )
            ->filter();

        /**
         * if the option hasn't been found
         * it will create a new Option model
         * and store with, then save it on the option model
         */
        if ( $foundOption->isEmpty() ) {
            $option = new Option;
            $option->key = trim( strtolower( $key ) );
            $option->array = false;

            $this->encodeOptionValue( $option, $value );

            $option->expire_on = $expiration;

            /**
             * this should be overridable
             * from a user option or any
             * extending this class
             */
            $option = $this->beforeSave( $option );
            $option->save();
        } else {
            $option = $foundOption->first();
        }

        /**
         * Let's save the new option
         */
        $this->rawOptions[ $key ] = $option;

        return $option;
    }

    /**
     * Encodes the value for the option before saving.
     */
    public function encodeOptionValue( Option $option, mixed $value ): void
    {
        if ( is_array( $value ) ) {
            $option->array = true;
            $option->value = json_encode( $value );
        } elseif ( empty( $value ) && ! (bool) preg_match( '/[0-9]{1,}/', $value ) ) {
            $option->value = '';
        } else {
            $option->value = $value;
        }
    }

    /**
     * Sanitizes values before storing on the database.
     */
    public function beforeSave( Option $option )
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
     **/
    public function get( ?string $key = null, mixed $default = null )
    {
        if ( $key === null ) {
            return $this->rawOptions;
        }

        $filtredOptions = collect( $this->rawOptions )->filter( function ( $option ) use ( $key ) {
            return is_array( $key ) ? in_array( $option->key, $key ) : $option->key === $key;
        } );

        $options = $filtredOptions->map( function ( $option ) {
            $this->decodeOptionValue( $option );

            return $option;
        } );

        return match ( $options->count() ) {
            0 => $default,
            1 => $options->first()->value,
            default => $options->map( fn( $option ) => $option->value )->toArray()
        };
    }

    public function decodeOptionValue( $option )
    {
        /**
         * We should'nt run this everytime we
         * try to pull an option from the database or from the array
         */
        if ( ! empty( $option->value ) && $option->isClean() ) {
            if ( is_string( $option->value ) && $option->array ) {
                $json = json_decode( $option->value, true );

                if ( json_last_error() == JSON_ERROR_NONE ) {
                    $option->value = $json;
                } else {
                    $option->value = null;
                }
            } elseif ( ! $option->array ) {
                if ( preg_match( '/^[0-9]{1,}$/', $option->value ) ) {
                    $option->value = (int) $option->value;
                } elseif ( preg_match( '/^[0-9]{1,}\.[0-9]{1,}$/', $option->value ) ) {
                    $option->value = (float) $option->value;
                } else {
                    $option->value = $option->value;
                }
            }
        }
    }

    /**
     * Delete an option using a specific key.
     **/
    public function delete( string $key ): void
    {
        $this->rawOptions = collect( $this->rawOptions )->filter( function ( Option $option ) use ( $key ) {
            if ( $option->key === $key ) {
                $option->delete();

                return false;
            }

            return true;
        } );
    }
}
