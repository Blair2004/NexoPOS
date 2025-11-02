<?php
namespace App\Classes;

class Menu
{
    public static function wrapper( ...$menus )
    {
        return array_merge( ...$menus );
    }

    public static function childrens( ...$childrens )
    {
        $childrens = collect( $childrens )->mapWithKeys( function ( $children ) {
            $key = array_keys( $children )[0];
            $value = array_values( $children )[0];

            return [ $key => $value ];
        } )->toArray();

        return $childrens;
    }

    public static function item( string $label, string $identifier, string $href = '', $icon = 'la la-star', $permissions = [], $show = true )
    {
        return [
            $identifier => [
                ...[
                    'label' => $label,
                    'href' => $href,
                    'icon' => $icon,
                    'show' => $show,
                ],
                ...( ! empty( $permissions ) ? [ 'permissions' => $permissions ] : [] ), // if no permission is set, it will not be included in the array
            ],
        ];
    }
}