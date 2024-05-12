<?php

namespace App\Filters;

class MenusFilter
{
    public static function injectRegisterMenus( $menus )
    {
        if ( ns()->option->get( 'ns_pos_registers_enabled' ) === 'yes' ) {
            $menus = array_insert_after( $menus, 'pos', [
                'registers' => [
                    'label' => __( 'POS' ),
                    'icon' => 'la-cash-register',
                    'permissions' => [ 'nexopos.create.orders' ],
                    'childrens' => [
                        'pos' => [
                            'label' => __( 'Open POS' ),
                            'href' => ns()->route( 'ns.dashboard.pos' ),
                        ],
                        'create' => [
                            'label' => __( 'Create Register' ),
                            'permissions' => [ 'nexopos.create.registers' ],
                            'href' => ns()->route( 'ns.dashboard.registers-create' ),
                        ],
                        'list' => [
                            'label' => __( 'Registers List' ),
                            'permissions' => [ 'nexopos.create.registers' ],
                            'href' => ns()->route( 'ns.dashboard.registers-list' ),
                        ],
                    ],
                ],
            ] );

            unset( $menus[ 'pos' ] );
        }

        if ( ns()->option->get( 'ns_orders_allow_unpaid' ) === 'yes' && isset( $menus[ 'orders' ] ) ) {
            $menus = array_insert_after( $menus, 'orders', [
                'orders' => [
                    'label' => __( 'Orders' ),
                    'icon' => 'la-list-ol',
                    'childrens' => array_merge(
                        $menus[ 'orders' ][ 'childrens' ], [
                            'instalments' => [
                                'label' => __( 'Instalments' ),
                                'permissions' => [ 'nexopos.read.orders-instalments' ],
                                'href' => ns()->route( 'ns.dashboard.orders-instalments' ),
                            ],
                        ] ),
                ],
            ] );
        }

        return $menus;
    }
}
