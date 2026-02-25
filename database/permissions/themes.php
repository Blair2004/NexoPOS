<?php

use App\Models\Permission;

if ( defined( 'NEXO_CREATE_PERMISSIONS' ) ) {
    $permission = Permission::firstOrNew( [ 'namespace' => 'manage.themes' ] );
    $permission->name = __( 'Manage Themes' );
    $permission->namespace = 'manage.themes';
    $permission->description = __( 'Allow the user to upload, enable, disable and delete themes.' );
    $permission->save();

    $permission = Permission::firstOrNew( [ 'namespace' => 'manage.theme.menus' ] );
    $permission->name = __( 'Manage Theme Menus' );
    $permission->namespace = 'manage.theme.menus';
    $permission->description = __( 'Allow the user to create and edit theme menus.' );
    $permission->save();

    $permission = Permission::firstOrNew( [ 'namespace' => 'manage.theme.pages' ] );
    $permission->name = __( 'Manage Theme Pages' );
    $permission->namespace = 'manage.theme.pages';
    $permission->description = __( 'Allow the user to create and edit theme pages.' );
    $permission->save();

    $permission = Permission::firstOrNew( [ 'namespace' => 'manage.theme.settings' ] );
    $permission->name = __( 'Manage Theme Settings' );
    $permission->namespace = 'manage.theme.settings';
    $permission->description = __( 'Allow the user to configure theme slugs and other settings.' );
    $permission->save();
}
