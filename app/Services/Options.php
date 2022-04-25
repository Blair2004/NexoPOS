<?php
namespace App\Services;

use App\Models\Option;
use App\Services\OptionWrapper;
use Illuminate\Support\Facades\Log;

class Options 
{
    private $rawOptions         =   [];
    private $options            =   [];
    private $isUserOptions      =   false;
    private $option;
    private $user_id;
    private $value;
    private $hasFound;
    private $removableIndex;

    /**
     * the option class can be constructed with the user id. If the user is not connected we
     * would like to avoid getting general option. So even if the user is not connected
     * we should treat null (when user is not connected) as if the 
     */
    public function __construct()
    {
        $this->build();
    }

    /**
     * return option service
     * @return object
     */
    public function option()
    {
        return Option::where( 'user_id', null );
    }

    /**
     * Build
     * Build option array
     * @return void
    **/

    public function build()
    {
        $this->options          =   [];

        if ( Helper::installed() ) {
            $this->rawOptions       =   $this->option()
                ->get()
                ->mapWithKeys( function( $option ) {
                    return [
                        $option->key    =>  $option
                    ];
                });
        }
    }

    /**
     * Set Option
     * @param string key
     * @param any value
     * @param boolean force set
     * @return void
    **/
    public function set( $key, $value, $expiration = null )
    {
        /**
         * if an option has been found,
         * it will save the new value and update
         * the option object.
         */
        $foundOption    =   collect( $this->rawOptions )->map( function( $option, $index ) use ( $value, $key, $expiration ) {
            if ( $key === $option->key ) {
                $this->hasFound         =   true;

                switch( $value ) {
                    case is_array( $value ) :
                        $option->value = json_encode( $value );
                    break;
                    case empty( $value ) && ! ( bool ) preg_match( '/[0-9]{1,}/', $value ) :
                        $option->value =    '';
                    break;
                    default:
                        $option->value  =   $value;
                    break;
                }
                
                $option->expire_on      =   $expiration;

                /**
                 * this should be overridable
                 * from a user option or any
                 * extending this class
                 */
                $option                 =   $this->beforeSave( $option );
                $option->save();

                return $option;
            }

            return false;
        })
        ->filter();

        /**
         * if the option hasn't been found
         * it will create a new Option model
         * and store with, then save it on the option model
         */
        if( $foundOption->empty() ) {
            $option               =   new Option;
            $option->key          =   trim( strtolower( $key ) );
            $option->array        =   false;

            switch( $value ) {
                case is_array( $value ) :
                    $option->value = json_encode( $value );
                break;
                case empty( $value ) && ! ( bool ) preg_match( '/[0-9]{1,}/', $value ) :
                    $option->value =    '';
                break;
                default:
                    $option->value  =   $value;
                break;
            }

            $option->expire_on    =   $expiration;

            /**
             * this should be overridable
             * from a user option or any
             * extending this class
             */
            $option                 =   $this->beforeSave( $option );            
            $option->save();
        } else {
            $option             =   $foundOption->first();
        }

        /**
         * Let's save the new option
         */
        $this->rawOptions[ $key ]     =   $option;
        
        return $option;
    }

    public function beforeSave( $option )
    {
        /**
        * sanitizing input to remove
        * all script tags
        */
        $option->value      =   strip_tags( $option->value );

        return $option;
    }

    /**
     * Get options
     * @param string|array $key
     * @return string|array|boolean|float
    **/
    public function get( $key = null, $default = null )
    {
        if ( $key === null ) {
            return $this->rawOptions;
        }

        $filtredOptions        =   collect( $this->rawOptions )->filter( function( $option ) use ( $key, $default ) {
            return is_array( $key ) ? in_array( $option->key, $key ) : $option->key === $key;
        });

        $options                =   $filtredOptions->map( function( $option ) {
            /**
             * We should'nt run this everytime we
             * try to pull an option from the database or from the array
             */
            if ( ! empty( $option->value ) && ! $option->parsed ) {
                if ( is_string( $option->value ) ) {
                    $json = json_decode( $option->value, true );

                    if ( json_last_error() == JSON_ERROR_NONE ) {
                        $option->value      =   $json;
                        $option->parsed     =   true;
                    } 
                } else if ( ! is_array( $option->value ) ) {
                    $option->parsed     =   true;
                    $option->value  =   match( $option->value ) {
                        preg_match( '/[0-9]{1,}/', $option->value ) =>  ( int ) $option->value, 
                        preg_match( '/[0-9]{1,}\.[0-9]{1,}/', $option->value ) =>  ( float ) $option->value, 
                        default =>  $option->value, 
                    };
                }
            }            
            
            return $option;
        });

        return match( $options->count() ) {
            0           => $default,
            1           => $options->first()->value,
            default     => $options->map( fn( $option ) => $option->value )->toArray()
        };
    }

    /**
     * Delete Key
     * @param string key
     * @return Eloquent Model Result
    **/
    public function delete( $key ) 
    {
        $this->rawOptions       =   collect( $this->rawOptions )->filter( function( $option ) use ( $key ) {
            if ( $option->key === $key ) {
                $option->delete();
                return false;
            }

            return true;
        });
    }
}