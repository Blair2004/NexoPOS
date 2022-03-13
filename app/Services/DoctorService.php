<?php
namespace App\Services;

use App\Models\Role;

class DoctorService
{
    /**
     * Will restore created roles
     */
    public function restoreRoles()
    {
        $rolesLabels    =   [
            Role::ADMIN         =>  [
                'label'     =>  __( 'Administrator' ),
                'dashid'    =>  Role::DASHID_STORE,
            ],
            Role::STOREADMIN    =>  [
                'label'     =>  __( 'Store Administrator' ),
                'dashid'    =>  Role::DASHID_STORE,
            ],
            Role::STORECASHIER  =>  [
                'label'     =>  __( 'Store Cashier' ),
                'dashid'    =>  Role::DASHID_CASHIER,
            ],
            Role::USER          =>  [
                'label'     =>  __( 'User' ),
                'dashid'    =>  Role::DASHID_DEFAULT,
            ],
        ];

        foreach( array_keys( $rolesLabels ) as $roleNamespace ) {
            $role      =   Role::namespace( $roleNamespace )->first();

            if ( ! $role instanceof Role ) {
                $role   =   new Role;
                $role->namespace    =   $roleNamespace;
                $role->name         =   $rolesLabels[ $roleNamespace ][ 'name' ];
                $role->dashid       =   $rolesLabels[ $roleNamespace ][ 'dashid' ];
                $role->locked       =   true;
                $role->save();
            }
        }
    }
}