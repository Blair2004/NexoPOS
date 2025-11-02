<?php

namespace App\Classes;

class AsideMenu extends Menu
{
    public static function menu( string $label, string $identifier, string $href = '', array $childrens = [], $icon = 'la la-star', $permissions = [], $counter = 0, $show = true )
    {
        return [
            $identifier => [
                ...[
                    'label' => $label,
                    'href' => $href,
                    'icon' => $icon,
                    'counter' => $counter,
                    'childrens' => $childrens,
                    'show' => $show,
                ],
                ...( ! empty( $permissions ) ? [ 'permissions' => $permissions ] : [] ), // if no permission is set, it will not be included in the array
            ],
        ];
    }

    public static function subMenu( string $label, string $identifier, string $href = '', $icon = 'la la-star', $permissions = [], $show = true )
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
