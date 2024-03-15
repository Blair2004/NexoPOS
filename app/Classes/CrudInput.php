<?php
namespace App\Services;

class CrudInput
{
    public static function text( $label, $name, $validation = '', $description = '', $readonly = false, $type = 'text' )
    {
        return compact( 'label', 'name', 'validation', 'description', 'readonly', 'type' );
    }

    public static function password( $label, $name, $validation = '', $description = '', $readonly = false ) 
    {
        return self::text(
            label: $label,
            name: $name,
            validation: $validation,
            description: $description,
            readonly: $readonly,
            type: 'password'
        );
    }

    public static function email( $label, $name, $validation = '', $description = '', $readonly = false ) 
    {
        return self::text(
            label: $label,
            name: $name,
            validation: $validation,
            description: $description,
            readonly: $readonly,
            type: 'email'
        );
    }

    public static function number( $label, $name, $validation = '', $description = '', $readonly = false, $type = 'number' ) 
    {
        return self::text(
            label: $label,
            name: $name,
            validation: $validation,
            description: $description,
            readonly: $readonly,
            type: 'number'
        );
    }

    public static function tel( $label, $name, $validation = '', $description = '', $readonly = false, $type = 'tel' ) 
    {
        return self::text(
            label: $label,
            name: $name,
            validation: $validation,
            description: $description,
            readonly: $readonly,
            type: 'tel'
        );
    }

    public static function hidden( $label, $name, $validation = '', $description = '', $readonly = false, $type = 'hidden' ) 
    {
        return self::text(
            label: $label,
            name: $name,
            validation: $validation,
            description: $description,
            readonly: $readonly,
            type: 'hidden'
        );
    }

    public static function date( $label, $name, $validation = '', $description = '', $readonly = false, $type = 'date' ) 
    {
        return self::text(
            label: $label,
            name: $name,
            validation: $validation,
            description: $description,
            readonly: $readonly,
            type: 'date'
        );
    }

    public static function select( $label, $name, $options, $validation = '', $description = '', $readonly = false, $type = 'text' )
    {
        return compact( 'label', 'name', 'validation', 'options', 'description', 'readonly', 'type' );
    }

    public static function searchSelect( $label, $name, $options = [], $validation = '', $description = '', $readonly = false )
    {
        return self::select(
            label: $label,
            name: $name,
            validation: $validation,
            description: $description,
            options: $options,
            type: 'search-select',
        );
    }

    public static function textarea( $label, $name, $validation = '', $description = '', $readonly = false ) 
    {
        return self::text(
            label: $label,
            name: $name,
            validation: $validation,
            description: $description,
            readonly: $readonly,
            type: 'textarea'
        );
    }

    public static function checkbox( $label, $name, $validation = '', $description = '', $readonly = false ) 
    {
        return self::text(
            label: $label,
            name: $name,
            validation: $validation,
            description: $description,
            readonly: $readonly,
            type: 'checkbox'
        );
    }

    public static function multiselect( $label, $name, $options, $validation = '', $description = '', $readonly = false ) 
    {
        return self::select(
            label: $label,
            name: $name,
            validation: $validation,
            options: $options,
            description: $description,
            readonly: $readonly,
            type: 'multiselect'
        );
    }

    public static function inlineMultiselect( $label, $name, $options, $validation = '', $description = '', $readonly = false )
    {
        return self::select(
            label: $label,
            name: $name,
            validation: $validation,
            options: $options,
            description: $description,
            readonly: $readonly,
            type: 'inline-multiselect'
        );
    }
    
    public static function selectAudio( $label, $name, $options, $validation = '', $description = '', $readonly = false ) 
    {
        return self::select(
            label: $label,
            name: $name,
            validation: $validation,
            options: $options,
            description: $description,
            readonly: $readonly,
            type: 'select-audio'
        );
    }

    public static function switch( $label, $name, $options, $validation = '', $description = '', $readonly = false ) 
    {
        return self::select(
            label: $label,
            name: $name,
            validation: $validation,
            options: $options,
            description: $description,
            readonly: $readonly,
            type: 'switch'
        );
    }
   
    public static function media( $label, $name, $validation = '', $description = '', $readonly = false ) 
    {
        return self::text(
            label: $label,
            name: $name,
            validation: $validation,
            description: $description,
            readonly: $readonly,
            type: 'media'
        );
    }

    public static function ckeditor( $label, $name, $validation = '', $description = '', $readonly = false ) 
    {
        return self::text(
            label: $label,
            name: $name,
            validation: $validation,
            description: $description,
            readonly: $readonly,
            type: 'ckeditor'
        );
    }
    
    public static function datetime( $label, $name, $validation = '', $description = '', $readonly = false ) 
    {
        return self::text(
            label: $label,
            name: $name,
            validation: $validation,
            description: $description,
            readonly: $readonly,
            type: 'datetimepicker'
        );
    }

    public static function daterange( $label, $name, $validation = '', $description = '', $readonly = false ) 
    {
        return self::text(
            label: $label,
            name: $name,
            validation: $validation,
            description: $description,
            readonly: $readonly,
            type: 'daterangepicker'
        );
    }

    public static function custom( $label, $name, $type, $validation = '', $description = '', $readonly = false, $options = [] ) 
    {
        return self::select(
            label: $label,
            name: $name,
            validation: $validation,
            description: $description,
            readonly: $readonly,
            options: $options,
            type: $type
        );
    }
}