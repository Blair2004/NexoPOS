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
            $this->rawOptions       =   $this->option()->get();
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

        /**
         * In case an array of keys are provided
         * those will be pulled and turned into an 
         * associative array with key value pair.
         */
        if ( is_array( $key ) ) {
            $array  =   [];
            foreach( $key as $_key ) {
                $array[ $_key ]     =   $this->rawOptions[ $key ] ?? $default;
            }

            return $array;
        }

        $this->value    =   $default !== null ? $default : null;

        collect( $this->rawOptions )->each( function( $option ) use ( $key, $default ) {
            if ( $option->key === $key ) {
                if ( 
                    ! empty( $option->value ) &&
                    ( bool ) preg_match( '/[0-9]{1,}/', $option->value ) &&
                    is_string( $option->value ) && 
                    is_array( $json = json_decode( $option->value, true ) ) && 
                    ( json_last_error() == JSON_ERROR_NONE ) 
                ) {
                    $this->value  =  $json;
                } else {
                    $this->value  =  (
                        empty( $option->value ) && $option->value === null && ( bool ) preg_match( '/[0-9]{1,}/', $option->value )
                    ) ? $default : $option->value;
                }
            }
        });
        
        return $this->value;
    }

    /**
     * Delete Key
     * @param string key
     * @return Eloquent Model Result
    **/
    public function delete( $key ) 
    {
        $this->removableIndex           =   null;
        collect( $this->rawOptions )->map( function( $option, $index ) use ( $key ) {
            if ( $option->key === $key ) {
                $option->delete();
                $this->removableIndex     =   $index;
            }
        });  

        if ( ! empty( $this->removableIndex ) ) {
            collect( $this->rawOptions )->offsetUnset( $this->removableIndex );
        }
    }
}