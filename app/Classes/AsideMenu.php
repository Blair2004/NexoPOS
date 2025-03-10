<?php
namespace App\Classes;

class AsideMenu 
{
    public static function menu( string $label, string $identifier, string $href = '', array $childrens = [], $icon = 'la la-star', $permissions = [], $counter = 0 )
    {
        return [
            $identifier => [
                ...[
                    'label'         =>  $label,
                    'href'           =>  $href,
                    'icon'          =>  $icon,
                    'counter'       =>  $counter,
                    'childrens'     =>  $childrens
                ],
                ...( !empty( $permissions ) ? [ 'permissions' => $permissions ] : [] ), // if no permission is set, it will not be included in the array
            ]
        ];
    }

    public static function wrapper( ...$menus )
    {
        return array_merge( ...$menus );
    }

    public static function subMenu( string $label, string $identifier, string $href = '', $icon = 'la la-star', $permissions = [] )
    {
        return [
            $identifier => [
                ...[
                    'label'         =>  $label,
                    'href'          =>  $href,
                    'icon'          =>  $icon,
                ],
                ...( !empty( $permissions ) ? [ 'permissions' => $permissions ] : [] ), // if no permission is set, it will not be included in the array
            ]
        ];
    }

    public static function childrens( ...$childrens )
    {
        $childrens  =   collect( $childrens )->mapWithKeys( function( $children) {
            $key        =  array_keys( $children )[0];
            $value      =  array_values( $children )[0];
            
            return [ $key => $value ];
        })->toArray();

        return $childrens;
    }
}