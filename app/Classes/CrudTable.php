<?php
namespace App\Classes;

class CrudTable
{
    public static function columns( ...$args )
    {
        return collect( $args )->mapWithKeys( function( $column ) {
            return [
                $column[ 'identifier' ] => $column
            ];
        })->toArray();
    }

    public static function column( $label, $identifier, $sort = false, $attributes = [] )
    {
        return compact( 'identifier', 'label', 'sort', 'attributes' );
    }

    public static function attribute( $label, $column )
    {
        return compact( 'label', 'column' );
    }

    public static function attributes( ...$attributes )
    {
        return $attributes;
    }
}