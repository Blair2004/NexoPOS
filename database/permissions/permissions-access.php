<?php

use App\Models\Permission;

if ( defined( 'NEXO_CREATE_PERMISSIONS' ) ) {
    $permissions = [
        'create.permissions-access' => [ __( 'Create Permission Requests' ), __( 'Request temporary permission for the current user.' ) ],
        'read.permissions-access' => [ __( 'Read All Permission Requests' ), __( 'Read temporary permission requests from all users.' ) ],
        'update.permissions-access' => [ __( 'Approve Permission Requests' ), __( 'Approve temporary requests for permissions the user holds.' ) ],
        'delete.permissions-access' => [ __( 'Delete Own Permission Requests' ), __( 'Delete the current user\'s pending permission requests.' ) ],
        'delete-all.permissions-access' => [ __( 'Delete All Permission Requests' ), __( 'Delete temporary permission requests from any user, including completed requests.' ) ],
    ];

    foreach ( $permissions as $namespace => [$name, $description] ) {
        if ( Permission::namespace( $namespace ) instanceof Permission ) {
            continue;
        }

        if ( Permission::where( 'name', $name )->exists() ) {
            throw new RuntimeException( sprintf( 'The permission name "%s" is already assigned to another namespace.', $name ) );
        }

        Permission::create( compact( 'namespace', 'name', 'description' ) );
    }
}
