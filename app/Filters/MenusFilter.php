<?php
namespace App\Filters;

class MenusFilter
{
    public static function injectRegisterMenus( $menus )
    {
        if ( ns()->option->get( 'ns_pos_registers_enabled' ) === 'yes' ) {
            $menus      =   array_insert_after( $menus, 'pos', [
                'registers'     =>      [
                    'label'     =>  __( 'POS' ),
                    'icon'      =>  'la-cash-register',
                    'childrens' =>  [
                        'pos'       =>  [
                            'label'     =>  __( 'Open POS' ),
                            'href'      =>  ns()->route( 'ns.dashboard.pos' ),
                        ],
                        'create'    =>  [
                            'label'     =>  __( 'Create Register' ),
                            'href'      =>  ns()->route( 'ns.dashboard.registers-create' ),
                        ],
                        'list'      =>  [
                            'label'     =>  __( 'Registes List' ),
                            'href'      =>  ns()->route( 'ns.dashboard.registers-list' ),
                        ]
                    ]
                ]
            ]);

            unset( $menus[ 'pos' ] );
        }

        return $menus;
    }
}