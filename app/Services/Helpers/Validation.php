<?php
namespace App\Services\Helpers;

trait Validation
{
    /**
     * Push Validation Rules to options key on config tendoo.validations.options
     * @param array of validation rule
     * @return void
     */
    static function pushValidationRule( $rules, $namespace = 'options' )
    {
        $validation     =   config( 'tendoo.validations.' . $namespace );
        $newValidation  =   array_merge( $validation, $rules );
        config([ 'tendoo.validations.' . $namespace => $newValidation ]);
    }

    /**
     * Use field validation
     * @param object<Field>
     * @return void
     */
    static function useFieldsValidation( $fields, $namespace = 'options' )
    {
        self::pushValidationRule( self::getFieldsValidation( $fields, $namespace ), $namespace );
    }

    /**
     * get field validation
     * @return array of field validation
     */
    static function getFieldsValidation( $fields, $namespace = 'options' )
    {
        $validation     =   [];
        foreach( $fields as $field ) {
            if ( @$field->validation ) {
                $validation[ $field->name ]   =   $field->validation;
            }
        }
        
        return $validation;
    }
}