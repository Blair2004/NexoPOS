<?php

use App\Models\Permission;

if ( defined( 'NEXO_CREATE_PERMISSIONS' ) ) {
    $medias = Permission::firstOrNew( [ 'namespace' => 'nexopos.upload.medias' ] );
    $medias->name = __( 'Upload Medias' );
    $medias->namespace = 'nexopos.upload.medias';
    $medias->description = __( 'Let the user upload medias.' );
    $medias->save();

    $medias = Permission::firstOrNew( [ 'namespace' => 'nexopos.see.medias' ] );
    $medias->name = __( 'See Medias' );
    $medias->namespace = 'nexopos.see.medias';
    $medias->description = __( 'Let the user see medias.' );
    $medias->save();

    $medias = Permission::firstOrNew( [ 'namespace' => 'nexopos.delete.medias' ] );
    $medias->name = __( 'Delete Medias' );
    $medias->namespace = 'nexopos.delete.medias';
    $medias->description = __( 'Let the user delete medias.' );
    $medias->save();

    $medias = Permission::firstOrNew( [ 'namespace' => 'nexopos.update.medias' ] );
    $medias->name = __( 'Update Medias' );
    $medias->namespace = 'nexopos.update.medias';
    $medias->description = __( 'Let the user update uploaded medias.' );
    $medias->save();
}
