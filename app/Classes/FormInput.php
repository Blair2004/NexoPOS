<?php

namespace App\Classes;

class FormInput
{
    /**
     * Creates a text input field with various customizable parameters.
     *
     * @param  string        $label       The label for the input field.
     * @param  string        $name        The name attribute for the input field.
     * @param  string        $value       The default value of the input field.
     * @param  string        $validation  Validation rules for the input field.
     * @param  string        $description A description or help text for the input field.
     * @param  bool          $disabled    Whether the input field is disabled.
     * @param  string        $type        The type of the input field (e.g., 'text', 'password').
     * @param  array         $errors      An array of error messages for the input field.
     * @param  array         $data        Additional data attributes for the input field. Now, we might include validation with suffix by adding "validate-with-suffix": true
     * @param  callable|null $show        A callable that determines whether the input field should be displayed.
     * @return array         An associative array representing the input field configuration.
     */
    public static function text( $label, $name, $value = '', $validation = '', $description = '', $disabled = false, $type = 'text', $errors = [], $data = [], $show = null, $suffix = '', $custom_errors = [] )
    {
        $field = compact( 'label', 'name', 'value', 'validation', 'description', 'disabled', 'type', 'errors', 'data', 'show', 'suffix', 'custom_errors' );

        /**
         * the password input relies on the text input. Therefore,
         * we should include cases where the value is set to "null" by the password and
         * not displayed on the form.
         */
        if ( $field[ 'value' ] === null ) {
            unset( $field[ 'value' ] );
        }

        return $field;
    }

    /**
     * Creates a password input field by reusing the text method with type set to 'password'.
     *
     * @param  string        $label       The label for the password field.
     * @param  string        $name        The name attribute for the password field.
     * @param  string        $value       The default value of the password field.
     * @param  string        $validation  Validation rules for the password field.
     * @param  string        $description A description or help text for the password field.
     * @param  bool          $disabled    Whether the password field is disabled.
     * @param  callable|null $show        A callable that determines whether the password field should be displayed.
     * @return array         An associative array representing the password field configuration.
     */
    public static function password( $label, $name, $value = '', $validation = '', $description = '', $disabled = false, $show = null, $custom_errors = [] )
    {
        return self::text(
            label: $label,
            name: $name,
            validation: $validation,
            description: $description,
            disabled: $disabled,
            type: 'password',
            value: $value,
            show: $show,
            custom_errors: $custom_errors
        );
    }

    /**
     * Creates an email input field by reusing the text method with type set to 'email'.
     *
     * @param  string        $label       The label for the email field.
     * @param  string        $name        The name attribute for the email field.
     * @param  string        $value       The default value of the email field.
     * @param  string        $validation  Validation rules for the email field.
     * @param  string        $description A description or help text for the email field.
     * @param  bool          $disabled    Whether the email field is disabled.
     * @param  callable|null $show        A callable that determines whether the email field should be displayed.
     * @return array         An associative array representing the email field configuration.
     */
    public static function email( $label, $name, $value = '', $validation = '', $description = '', $disabled = false, $show = null, $custom_errors = [] )
    {
        return self::text(
            label: $label,
            name: $name,
            validation: $validation,
            description: $description,
            disabled: $disabled,
            type: 'email',
            value: $value,
            show: $show,
            custom_errors: $custom_errors
        );
    }

    /**
     * Creates a number input field by reusing the text method with type set to 'number'.
     *
     * @param  string        $label       The label for the number field.
     * @param  string        $name        The name attribute for the number field.
     * @param  string        $value       The default value of the number field.
     * @param  string        $validation  Validation rules for the number field.
     * @param  string        $description A description or help text for the number field.
     * @param  bool          $disabled    Whether the number field is disabled.
     * @param  array         $errors      An array of error messages for the number field.
     * @param  callable|null $show        A callable that determines whether the number field should be displayed.
     * @return array         An associative array representing the number field configuration.
     */
    public static function number( $label, $name, $value = '', $validation = '', $description = '', $disabled = false, $errors = [], $show = null, $custom_errors = [] )
    {
        return self::text(
            label: $label,
            name: $name,
            validation: $validation,
            description: $description,
            disabled: $disabled,
            type: 'number',
            value: $value,
            errors: $errors,
            show: $show,
            custom_errors: $custom_errors
        );
    }

    /**
     * Creates a telephone input field by reusing the text method with type set to 'tel'.
     *
     * @param  string        $label       The label for the telephone field.
     * @param  string        $name        The name attribute for the telephone field.
     * @param  string        $value       The default value of the telephone field.
     * @param  string        $validation  Validation rules for the telephone field.
     * @param  string        $description A description or help text for the telephone field.
     * @param  bool          $disabled    Whether the telephone field is disabled.
     * @param  string        $type        The type of the input field (default is 'tel').
     * @param  callable|null $show        A callable that determines whether the telephone field should be displayed.
     * @return array         An associative array representing the telephone field configuration.
     */
    public static function tel( $label, $name, $value = '', $validation = '', $description = '', $disabled = false, $type = 'tel', $show = null, $custom_errors = [] )
    {
        return self::text(
            label: $label,
            name: $name,
            validation: $validation,
            description: $description,
            disabled: $disabled,
            type: 'tel',
            value: $value,
            show: $show,
            custom_errors: $custom_errors
        );
    }

    /**
     * Creates a hidden input field by reusing the text method with type set to 'hidden'.
     *
     * @param  string        $name        The name attribute for the hidden field.
     * @param  string        $label       The label for the hidden field.
     * @param  string        $value       The default value of the hidden field.
     * @param  string        $validation  Validation rules for the hidden field.
     * @param  string        $description A description or help text for the hidden field.
     * @param  bool          $disabled    Whether the hidden field is disabled.
     * @param  array         $errors      An array of error messages for the hidden field.
     * @param  callable|null $show        A callable that determines whether the hidden field should be displayed.
     * @return array         An associative array representing the hidden field configuration.
     */
    public static function hidden( $name, $label = '', $value = '', $validation = '', $description = '', $disabled = false, $errors = [], $show = null )
    {
        return self::text(
            label: $label,
            name: $name,
            validation: $validation,
            description: $description,
            disabled: $disabled,
            type: 'hidden',
            value: $value,
            show: $show
        );
    }

    /**
     * Creates a date input field by reusing the text method with type set to 'date'.
     *
     * @param  string        $label       The label for the date field.
     * @param  string        $name        The name attribute for the date field.
     * @param  string        $value       The default value of the date field.
     * @param  string        $validation  Validation rules for the date field.
     * @param  string        $description A description or help text for the date field.
     * @param  bool          $disabled    Whether the date field is disabled.
     * @param  callable|null $show        A callable that determines whether the date field should be displayed.
     * @return array         An associative array representing the date field configuration.
     */
    public static function date( $label, $name, $value = '', $validation = '', $description = '', $disabled = false, $show = null )
    {
        return self::text(
            label: $label,
            name: $name,
            validation: $validation,
            description: $description,
            disabled: $disabled,
            type: 'date',
            value: $value,
            show: $show
        );
    }

    /**
     * Creates a select dropdown field with customizable options and parameters.
     *
     * @param  string        $label       The label for the select field.
     * @param  string        $name        The name attribute for the select field.
     * @param  array         $options     An array of options for the select field.
     * @param  string        $value       The default value of the select field.
     * @param  string        $validation  Validation rules for the select field.
     * @param  string        $description A description or help text for the select field.
     * @param  bool          $disabled    Whether the select field is disabled.
     * @param  string        $type        The type of the select field (default is 'select').
     * @param  string        $component   The component associated with the select field.
     * @param  array         $props       Additional properties for the select field.
     * @param  bool          $refresh     Whether the select field should be refreshable.
     * @param  array         $errors      An array of error messages for the select field.
     * @param  callable|null $show        A callable that determines whether the select field should be displayed.
     * @return array         An associative array representing the select field configuration.
     */
    public static function select( $label, $name, $options, $value = '', $validation = '', $description = '', $disabled = false, $type = 'select', $component = '', $props = [], $refresh = false, $errors = [], $show = null )
    {
        return compact( 'label', 'name', 'validation', 'options', 'value', 'description', 'disabled', 'type', 'component', 'props', 'refresh', 'errors', 'show' );
    }

    /**
     * Creates a searchable select dropdown field by reusing the select method with type set to 'search-select'.
     *
     * @param  string        $label       The label for the searchable select field.
     * @param  string        $name        The name attribute for the searchable select field.
     * @param  string        $value       The default value of the searchable select field.
     * @param  array         $options     An array of options for the searchable select field.
     * @param  string        $validation  Validation rules for the searchable select field.
     * @param  string        $description A description or help text for the searchable select field.
     * @param  bool          $disabled    Whether the searchable select field is disabled.
     * @param  string        $component   The component associated with the searchable select field.
     * @param  array         $props       Additional properties for the searchable select field.
     * @param  bool          $refresh     Whether the searchable select field should be refreshable.
     * @param  array         $errors      An array of error messages for the searchable select field.
     * @param  callable|null $show        A callable that determines whether the searchable select field should be displayed.
     * @return array         An associative array representing the searchable select field configuration.
     */
    public static function searchSelect( $label, $name, $value = '', $options = [], $validation = '', $description = '', $disabled = false, $component = '', $props = [], $refresh = false, $errors = [], $show = null )
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
            errors: $errors,
            show: $show
        );
    }

    /**
     * Configures a refreshable component with a URL and watch parameters.
     *
     * @param  string $url   The URL to refresh the component.
     * @param  string $watch The parameter to watch for changes.
     * @param  array  $data  Additional data attributes for the refreshable component.
     * @return array  An associative array representing the refresh configuration.
     */
    public static function refreshConfig( string $url, string $watch, array $data = [] )
    {
        return compact( 'url', 'watch', 'data' );
    }

    /**
     * Creates a textarea input field by reusing the text method with type set to 'textarea'.
     *
     * @param  string        $label       The label for the textarea field.
     * @param  string        $name        The name attribute for the textarea field.
     * @param  string        $value       The default value of the textarea field.
     * @param  string        $validation  Validation rules for the textarea field.
     * @param  string        $description A description or help text for the textarea field.
     * @param  bool          $disabled    Whether the textarea field is disabled.
     * @param  array         $data        Additional data attributes for the textarea field.
     * @param  callable|null $show        A callable that determines whether the textarea field should be displayed.
     * @return array         An associative array representing the textarea field configuration.
     */
    public static function textarea( $label, $name, $value = '', $validation = '', $description = '', $disabled = false, $data = [], $show = null, $custom_errors = [] )
    {
        return self::text(
            label: $label,
            name: $name,
            validation: $validation,
            description: $description,
            disabled: $disabled,
            type: 'textarea',
            value: $value,
            data: $data,
            show: $show,
            custom_errors: $custom_errors
        );
    }

    /**
     * Creates a checkbox input field by reusing the text method with type set to 'checkbox'.
     *
     * @param  string        $label       The label for the checkbox field.
     * @param  string        $name        The name attribute for the checkbox field.
     * @param  string        $value       The default value of the checkbox field.
     * @param  string        $validation  Validation rules for the checkbox field.
     * @param  string        $description A description or help text for the checkbox field.
     * @param  bool          $disabled    Whether the checkbox field is disabled.
     * @param  callable|null $show        A callable that determines whether the checkbox field should be displayed.
     * @return array         An associative array representing the checkbox field configuration.
     */
    public static function checkbox( $label, $name, $value = '', $validation = '', $description = '', $disabled = false, $show = null )
    {
        return self::text(
            label: $label,
            name: $name,
            validation: $validation,
            description: $description,
            disabled: $disabled,
            type: 'checkbox',
            value: $value,
            show: $show
        );
    }

    /**
     * Creates a multiselect dropdown field by reusing the select method with type set to 'multiselect'.
     *
     * @param  string        $label       The label for the multiselect field.
     * @param  string        $name        The name attribute for the multiselect field.
     * @param  array         $options     An array of options for the multiselect field.
     * @param  string        $value       The default value of the multiselect field.
     * @param  string        $validation  Validation rules for the multiselect field.
     * @param  string        $description A description or help text for the multiselect field.
     * @param  bool          $disabled    Whether the multiselect field is disabled.
     * @param  callable|null $show        A callable that determines whether the multiselect field should be displayed.
     * @return array         An associative array representing the multiselect field configuration.
     */
    public static function multiselect( $label, $name, $options, $value = '', $validation = '', $description = '', $disabled = false, $show = null )
    {
        return self::select(
            label: $label,
            name: $name,
            validation: $validation,
            options: $options,
            description: $description,
            disabled: $disabled,
            type: 'multiselect',
            value: $value,
            show: $show
        );
    }

    /**
     * Creates an inline multiselect dropdown field by reusing the select method with type set to 'inline-multiselect'.
     *
     * @param  string        $label       The label for the inline multiselect field.
     * @param  string        $name        The name attribute for the inline multiselect field.
     * @param  string        $value       The default value of the inline multiselect field.
     * @param  array         $options     An array of options for the inline multiselect field.
     * @param  string        $validation  Validation rules for the inline multiselect field.
     * @param  string        $description A description or help text for the inline multiselect field.
     * @param  bool          $disabled    Whether the inline multiselect field is disabled.
     * @param  callable|null $show        A callable that determines whether the inline multiselect field should be displayed.
     * @return array         An associative array representing the inline multiselect field configuration.
     */
    public static function inlineMultiselect( $label, $name, $value, $options, $validation = '', $description = '', $disabled = false, $show = null )
    {
        return self::select(
            label: $label,
            name: $name,
            validation: $validation,
            options: $options,
            description: $description,
            disabled: $disabled,
            type: 'inline-multiselect',
            value: $value,
            show: $show
        );
    }

    /**
     * Creates a select dropdown field for audio files by reusing the select method with type set to 'select-audio'.
     *
     * @param  string        $label       The label for the select audio field.
     * @param  string        $name        The name attribute for the select audio field.
     * @param  string        $value       The default value of the select audio field.
     * @param  array         $options     An array of options for the select audio field.
     * @param  string        $validation  Validation rules for the select audio field.
     * @param  string        $description A description or help text for the select audio field.
     * @param  bool          $disabled    Whether the select audio field is disabled.
     * @param  callable|null $show        A callable that determines whether the select audio field should be displayed.
     * @return array         An associative array representing the select audio field configuration.
     */
    public static function selectAudio( $label, $name, $value, $options, $validation = '', $description = '', $disabled = false, $show = null )
    {
        return self::select(
            label: $label,
            name: $name,
            validation: $validation,
            options: $options,
            description: $description,
            disabled: $disabled,
            type: 'select-audio',
            value: $value,
            show: $show
        );
    }

    /**
     * Creates a switch input field by reusing the select method with type set to 'switch'.
     *
     * @param  string        $label       The label for the switch field.
     * @param  string        $name        The name attribute for the switch field.
     * @param  array         $options     An array of options for the switch field.
     * @param  string        $value       The default value of the switch field.
     * @param  string        $validation  Validation rules for the switch field.
     * @param  string        $description A description or help text for the switch field.
     * @param  bool          $disabled    Whether the switch field is disabled.
     * @param  array         $errors      An array of error messages for the switch field.
     * @param  callable|null $show        A callable that determines whether the switch field should be displayed.
     * @return array         An associative array representing the switch field configuration.
     */
    public static function switch( $label, $name, $options, $value = '', $validation = '', $description = '', $disabled = false, $errors = [], $show = null )
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
            errors: $errors,
            show: $show
        );
    }

    /**
     * Creates a media input field by reusing the text method with type set to 'media'.
     *
     * @param  string        $label       The label for the media field.
     * @param  string        $name        The name attribute for the media field.
     * @param  string        $value       The default value of the media field.
     * @param  string        $validation  Validation rules for the media field.
     * @param  string        $description A description or help text for the media field.
     * @param  bool          $disabled    Whether the media field is disabled.
     * @param  array         $errors      An array of error messages for the media field.
     * @param  array         $data        Additional data attributes for the media field (default is ['type' => 'url']).
     * @param  callable|null $show        A callable that determines whether the media field should be displayed.
     * @return array         An associative array representing the media field configuration.
     */
    public static function media( $label, $name, $value = '', $validation = '', $description = '', $disabled = false, $errors = [], $data = [ 'type' => 'url' ], $show = null )
    {
        return self::text(
            label: $label,
            name: $name,
            validation: $validation,
            description: $description,
            disabled: $disabled,
            type: 'media',
            value: $value,
            errors: $errors,
            data: $data,
            show: $show
        );
    }

    /**
     * Creates a CKEditor input field by reusing the text method with type set to 'ckeditor'.
     *
     * @param  string        $label       The label for the CKEditor field.
     * @param  string        $name        The name attribute for the CKEditor field.
     * @param  string        $value       The default value of the CKEditor field.
     * @param  string        $validation  Validation rules for the CKEditor field.
     * @param  string        $description A description or help text for the CKEditor field.
     * @param  bool          $disabled    Whether the CKEditor field is disabled.
     * @param  callable|null $show        A callable that determines whether the CKEditor field should be displayed.
     * @return array         An associative array representing the CKEditor field configuration.
     */
    public static function ckeditor( $label, $name, $value = '', $validation = '', $description = '', $disabled = false, $show = null )
    {
        return self::text(
            label: $label,
            name: $name,
            validation: $validation,
            description: $description,
            disabled: $disabled,
            type: 'ckeditor',
            value: $value,
            show: $show
        );
    }

    /**
     * Creates a datetime picker input field by reusing the text method with type set to 'datetimepicker'.
     *
     * @param  string        $label       The label for the datetime picker field.
     * @param  string        $name        The name attribute for the datetime picker field.
     * @param  string        $value       The default value of the datetime picker field.
     * @param  string        $validation  Validation rules for the datetime picker field.
     * @param  string        $description A description or help text for the datetime picker field.
     * @param  bool          $disabled    Whether the datetime picker field is disabled.
     * @param  callable|null $show        A callable that determines whether the datetime picker field should be displayed.
     * @return array         An associative array representing the datetime picker field configuration.
     */
    public static function datetime( $label, $name, $value = '', $validation = '', $description = '', $disabled = false, $show = null )
    {
        return self::text(
            label: $label,
            name: $name,
            validation: $validation,
            description: $description,
            disabled: $disabled,
            type: 'datetimepicker',
            value: $value,
            show: $show
        );
    }

    /**
     * Creates a date range picker input field by reusing the text method with type set to 'daterangepicker'.
     *
     * @param  string        $label       The label for the date range picker field.
     * @param  string        $name        The name attribute for the date range picker field.
     * @param  string        $value       The default value of the date range picker field.
     * @param  string        $validation  Validation rules for the date range picker field.
     * @param  string        $description A description or help text for the date range picker field.
     * @param  bool          $disabled    Whether the date range picker field is disabled.
     * @param  callable|null $show        A callable that determines whether the date range picker field should be displayed.
     * @return array         An associative array representing the date range picker field configuration.
     */
    public static function daterange( $label, $name, $value = '', $validation = '', $description = '', $disabled = false, $show = null )
    {
        return self::text(
            label: $label,
            name: $name,
            validation: $validation,
            description: $description,
            disabled: $disabled,
            type: 'daterangepicker',
            value: $value,
            show: $show
        );
    }

    /**
     * Creates a custom input field with a specified component.
     *
     * @param  string        $label     The label for the custom field.
     * @param  string        $component The component associated with the custom field.
     * @param  callable|null $show      A callable that determines whether the custom field should be displayed.
     * @return array         An associative array representing the custom field configuration.
     */
    public static function custom( $label, $component, $show = null )
    {
        return [
            'label' => $label,
            'type' => 'custom',
            'component' => $component,
            'show' => $show,
        ];
    }
}
