<?php

namespace App\Classes;

class CrudInput
{
    public static function text( $label, $name, $value = '', $validation = '', $description = '', $disabled = false, $type = 'text', $errors = [] )
    {
        return compact( 'label', 'name', 'value', 'validation', 'description', 'disabled', 'type', 'errors' );
    }

    public static function password( $label, $name, $value = '', $validation = '', $description = '', $disabled = false )
    {
        return self::text(
            label: $label,
            name: $name,
            validation: $validation,
            description: $description,
            disabled: $disabled,
            type: 'password',
            value: $value
        );
    }

    public static function email( $label, $name, $value = '', $validation = '', $description = '', $disabled = false )
    {
        return self::text(
            label: $label,
            name: $name,
            validation: $validation,
            description: $description,
            disabled: $disabled,
            type: 'email',
            value: $value
        );
    }

    public static function number( $label, $name, $value = '', $validation = '', $description = '', $disabled = false, $errors = [] )
    {
        return self::text(
            label: $label,
            name: $name,
            validation: $validation,
            description: $description,
            disabled: $disabled,
            type: 'number',
            value: $value,
            errors: $errors
        );
    }

    public static function tel( $label, $name, $value = '', $validation = '', $description = '', $disabled = false, $type = 'tel' )
    {
        return self::text(
            label: $label,
            name: $name,
            validation: $validation,
            description: $description,
            disabled: $disabled,
            type: 'tel',
            value: $value
        );
    }

    public static function hidden( $label, $name, $value = '', $validation = '', $description = '', $disabled = false, $errors = [] )
    {
        return self::text(
            label: $label,
            name: $name,
            validation: $validation,
            description: $description,
            disabled: $disabled,
            type: 'hidden',
            value: $value
        );
    }

    public static function date( $label, $name, $value = '', $validation = '', $description = '', $disabled = false )
    {
        return self::text(
            label: $label,
            name: $name,
            validation: $validation,
            description: $description,
            disabled: $disabled,
            type: 'date',
            value: $value
        );
    }

    public static function select( $label, $name, $options, $value = '', $validation = '', $description = '', $disabled = false, $type = 'select', $component = '', $props = [], $refresh = false, $errors = [] )
    {
        return compact( 'label', 'name', 'validation', 'options', 'value', 'description', 'disabled', 'type', 'component', 'props', 'refresh', 'errors' );
    }

    public static function searchSelect( $label, $name, $value = '', $options = [], $validation = '', $description = '', $disabled = false, $component = '', $props = [], $refresh = false, $errors = [] )
    {
        return self::select(
            label: $label,
            name: $name,
            validation: $validation,
            description: $description,
            options: $options,
            value: $value,
            type: 'search-select',
            component: $component,
            props: $props,
            disabled: $disabled,
            refresh: $refresh,
            errors: $errors
        );
    }

    public static function refreshConfig( string $url, string $watch, array $data = [] )
    {
        return compact( 'url', 'watch', 'data' );
    }

    public static function textarea( $label, $name, $value = '', $validation = '', $description = '', $disabled = false )
    {
        return self::text(
            label: $label,
            name: $name,
            validation: $validation,
            description: $description,
            disabled: $disabled,
            type: 'textarea',
            value: $value
        );
    }

    public static function checkbox( $label, $name, $value = '', $validation = '', $description = '', $disabled = false )
    {
        return self::text(
            label: $label,
            name: $name,
            validation: $validation,
            description: $description,
            disabled: $disabled,
            type: 'checkbox',
            value: $value
        );
    }

    public static function multiselect( $label, $name, $value, $options, $validation = '', $description = '', $disabled = false )
    {
        return self::select(
            label: $label,
            name: $name,
            validation: $validation,
            options: $options,
            description: $description,
            disabled: $disabled,
            type: 'multiselect',
            value: $value
        );
    }

    public static function inlineMultiselect( $label, $name, $value, $options, $validation = '', $description = '', $disabled = false )
    {
        return self::select(
            label: $label,
            name: $name,
            validation: $validation,
            options: $options,
            description: $description,
            disabled: $disabled,
            type: 'inline-multiselect',
            value: $value
        );
    }

    public static function selectAudio( $label, $name, $value, $options, $validation = '', $description = '', $disabled = false )
    {
        return self::select(
            label: $label,
            name: $name,
            validation: $validation,
            options: $options,
            description: $description,
            disabled: $disabled,
            type: 'select-audio',
            value: $value
        );
    }

    public static function switch( $label, $name, $options, $value = '', $validation = '', $description = '', $disabled = false, $errors = [] )
    {
        return self::select(
            label: $label,
            name: $name,
            validation: $validation,
            options: $options,
            value: $value,
            description: $description,
            disabled: $disabled,
            type: 'switch',
            errors: $errors
        );
    }

    public static function media( $label, $name, $value = '', $validation = '', $description = '', $disabled = false, $errors = [] )
    {
        return self::text(
            label: $label,
            name: $name,
            validation: $validation,
            description: $description,
            disabled: $disabled,
            type: 'media',
            value: $value,
            errors: $errors
        );
    }

    public static function ckeditor( $label, $name, $value = '', $validation = '', $description = '', $disabled = false )
    {
        return self::text(
            label: $label,
            name: $name,
            validation: $validation,
            description: $description,
            disabled: $disabled,
            type: 'ckeditor',
            value: $value
        );
    }

    public static function datetime( $label, $name, $value = '', $validation = '', $description = '', $disabled = false )
    {
        return self::text(
            label: $label,
            name: $name,
            validation: $validation,
            description: $description,
            disabled: $disabled,
            type: 'datetimepicker',
            value: $value
        );
    }

    public static function daterange( $label, $name, $value = '', $validation = '', $description = '', $disabled = false )
    {
        return self::text(
            label: $label,
            name: $name,
            validation: $validation,
            description: $description,
            disabled: $disabled,
            type: 'daterangepicker',
            value: $value
        );
    }

    public static function custom( $label, $component )
    {
        return [
            'label' => $label,
            'component' => $component,
        ];
    }
}
