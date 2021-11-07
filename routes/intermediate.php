<?php
/**
 * @todo need to have explanation on why we need this.
 */
use Illuminate\Support\Facades\Route;

Route::get( '/intermediate/{route}/{from}', function( $route, $from ) {
    $message    =   null;

    switch( $from ) {
        case 'ns.password-lost':
            $message    =   __( 'The recovery email has been send to your inbox.' );
        break;
        case 'ns.password-updated':
            $message    =   __( 'Your password has been successfully updated. You can login with your new password now.' );
        break;
    }

    return redirect( route( $route ) )->with( 'message', $message );
})->name( 'ns.intermediate' );