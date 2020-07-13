<?php
namespace Tendoo\Core\Services;

use Tendoo\Core\Models\Option;
use Tendoo\Core\Services\OptionWrapper;
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
        $this->rawOptions       =   $this->option()->get();
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
        $this->hasFound     =   false;
        $storedOption       =   null;
        
        /**
         * if an option has been found,
         * it will save the new value and update
         * the option object.
         */
        $this->rawOptions->map( function( $option, $index ) use ( $value, $key, $expiration, &$storedOption ) {
            if ( $key === $option->key ) {
                $this->hasFound         =   true;

                switch( $value ) {
                    case is_array( $value ) :
                        $option->value = json_encode( $value );
                    break;
                    case empty( $value ) :
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

                /**
                 * populate the variable
                 * that we'll return
                 */
                $storedOption           =   $option;
            }
        });

        /**
         * if the option hasn't been found
         * it will create a new Option model
         * and store with, then save it on the option model
         */
        if( ! $this->hasFound ) {
            $this->option               =   new Option;
            $this->option->key          =   trim( strtolower( $key ) );
            $this->option->array        =   false;

            switch( $value ) {
                case is_array( $value ) :
                    $this->option->value = json_encode( $value );
                break;
                case empty( $value ) :
                    $this->option->value =    '';
                break;
                default:
                    $this->option->value  =   $value;
                break;
            }

            $this->option->expire_on    =   $expiration;

            /**
             * this should be overridable
             * from a user option or any
             * extending this class
             */
            $this->option                 =   $this->beforeSave( $this->option );            
            $this->option->save();

            /**
             * populate the variable
             * that we'll return
             */
            $storedOption               =   $this->option;
        }

        /**
         * Let's save the new option
         */
        $this->rawOptions[ $key ]     =   $storedOption;
        
        return $storedOption;
    }

    public function beforeSave( $option )
    {
        return $option;
    }

    /**
     * Get options
     * @param string/array key
     * @return any
    **/
    public function get( $key = null, $default = null )
    {
        if ( $key === null ) {
            return $this->rawOptions;
        }

        $this->value    =   $default !== null ? $default : null;

        $this->rawOptions->map( function( $option ) use ( $key, $default ) {
            if ( $option->key === $key ) {
                if ( 
                    ! empty( $option->value ) &&
                    is_string( $option->value ) && 
                    is_array( $json = json_decode( $option->value, true ) ) && 
                    ( json_last_error() == JSON_ERROR_NONE ) 
                ) {
                    $this->value  =  $json;
                } else {
                    $this->value  =  empty( $option->value ) ? $default : $option->value;
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
        $this->rawOptions->map( function( $option, $index ) use ( $key ) {
            if ( $option->key === $key ) {
                $option->delete();
                $this->removableIndex     =   $index;
            }
        });  

        if ( ! empty( $this->removableIndex ) ) {
            $this->rawOptions->offsetUnset( $this->removableIndex );
        }
    }
}