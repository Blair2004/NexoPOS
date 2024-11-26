<?php

namespace App\Classes;

class CrudForm
{
    public static function form( $main, $tabs )
    {
        return compact( 'main', 'tabs' );
    }

    public static function tabs( ...$args )
    {
        return collect( $args )->mapWithKeys( function ( $tab ) {
            return [ $tab['identifier'] => $tab ];
        } )->toArray();
    }

    /**
     * @param string $identifier Provides a unique identifier for the tab
     * @param string $label This is a visible name used to identify the tab
     * @param array $fields Here are defined the fields that will be loaded on the tab
     * @param array $notices You might display specific notices on the tab
     * @param array $footer You can set a specfic feature that should be loaded at the footer
     */
    public static function tab( $identifier, $label, $fields, $notices = [], $footer = [] )
    {
        return compact( 'label', 'fields', 'identifier', 'notices', 'footer' );
    }

    public static function fields( ...$args )
    {
        return collect( $args )->filter()->toArray();
    }

    public static function tabFooter( $extraComponents = [] )
    {
        return compact( 'extraComponents' );
    }
}
