<?php

namespace App\Services;

use Exception;

class Validation
{
    protected $class;

    /**
     * let's define the class
     *
     * @param string from class
     * @return self
     */
    public function from( $class )
    {
        $this->class = $class;

        return $this;
    }

    /**
     * method to call which will extract
     * the validation fields
     *
     * @param string method to call
     * @return array
     */
    public function extract( $method, $model = null )
    {
        if ( class_exists( $this->class ) ) {
            $object = new $this->class;
            $fields = $object->$method( $model );
            $validation = collect( $fields )->mapWithKeys( function ( $field ) {
                return [
                    $field->name => ! empty( $field->validation ) ? $field->validation : '',
                ];
            } )->toArray();

            return $validation;
        }

        throw new Exception( sprintf( __( 'unable to find this validation class %s.' ), $this->class ) );
    }
}
