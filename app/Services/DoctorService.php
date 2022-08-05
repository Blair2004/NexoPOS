<?php

namespace App\Services;

use App\Models\Option;
use App\Models\Role;
use App\Models\User;
use App\Models\UserAttribute;
use Exception;

class DoctorService
{
    public function createUserAttribute(): array
    {
        User::get()->each( function( User $user ) {
            if ( ! $user->attribute instanceof UserAttribute ) {
                $attribute = new UserAttribute;
                $attribute->user_id = $user->id;
                $attribute->language = ns()->option->get( 'ns_store_language', 'en' );
                $attribute->theme = ns()->option->get( 'ns_default_theme', 'dark' );
                $attribute->save();
            }
        });

        return [
            'status' => 'success',
            'message' => __( 'The user attributes has been updated.' ),
        ];
    }

    /**
     * Will restore created roles
     */
    public function restoreRoles()
    {
        $rolesLabels = [
            Role::ADMIN => [
                'name' => __( 'Administrator' ),
                'dashid' => Role::DASHID_STORE,
            ],
            Role::STOREADMIN => [
                'name' => __( 'Store Administrator' ),
                'dashid' => Role::DASHID_STORE,
            ],
            Role::STORECASHIER => [
                'name' => __( 'Store Cashier' ),
                'dashid' => Role::DASHID_CASHIER,
            ],
            Role::USER => [
                'name' => __( 'User' ),
                'dashid' => Role::DASHID_DEFAULT,
            ],
        ];

        foreach ( array_keys( $rolesLabels ) as $roleNamespace ) {
            $role = Role::where( 'namespace', $roleNamespace )
                ->first();

            if ( ! $role instanceof Role ) {
                Role::where( 'name', $rolesLabels[ $roleNamespace ][ 'name' ] )->delete();

                $role = new Role;
                $role->namespace = $roleNamespace;
                $role->name = $rolesLabels[ $roleNamespace ][ 'name' ];
                $role->dashid = $rolesLabels[ $roleNamespace ][ 'dashid' ];
                $role->locked = true;
                $role->save();
            }
        }
    }

    public function fixDuplicateOptions()
    {
        $options = Option::get();
        $options->each( function( $option ) {
            try {
                $option->refresh();
                if ( $option instanceof Option ) {
                    Option::where( 'key', $option->key )
                        ->where( 'id', '<>', $option->id )
                        ->delete();
                }
            } catch ( Exception $exception ) {
                // the option might be deleted, let's skip that.
            }
        });
    }
}
