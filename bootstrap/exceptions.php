<?php
/**
 * if the debugging is truthy, we'll
 * then use the default behavior.
 */

use App\Services\Helper;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * If the App Debug is enabled, we should
 * let the app handle the exceptions itself.
 */
if ( env( 'APP_DEBUG' ) ) {
    return;
}

/**
 * @var Exceptions $exceptions
 */
$exceptions->render( function ( AuthenticationException $exception, Request $request ) {
    if ( $request->expectsJson() ) {
        return response()->json( [ 'message' => $exception->getMessage() ], 401 );
    } else {
        return redirect()->guest( ns()->route( 'ns.login' ) );
    }
} );

/**
 * A list of the inputs that are never flashed for validation exceptions.
 */
$exceptions->dontFlash( [
    'password',
    'password_confirmation',
    'password_confirm',
] );

$exceptions->render( function ( NotFoundHttpException $exception, Request $request ) {
    $title = __( 'Page Not Found' );
    $back = Helper::getValidPreviousUrl( $request );
    $message = $exception->getMessage() ?: __( 'The page you are looking for could not be found.' );

    if ( $request->expectsJson() ) {
        return response()->json( [ 'message' => $message ], 404 );
    } else {
        return response()->view( 'pages.errors.not-found-exception', compact( 'message', 'title', 'back' ), 404 );
    }
} );

$exceptions->render( function ( Exception $exception, Request $request ) {
    $title = __( 'An Error Occured' );
    $back = Helper::getValidPreviousUrl( $request );
    $message = $exception->getMessage() ?: sprintf( __( 'Class: %s' ), get_class( $exception ) );
    $exploded = explode( '(View', $message );
    $message = $exploded[0] ?? $message;

    if ( $request->expectsJson() ) {
        return response()->json( [
            'status' => 'error',
            'message' => $message,
            'previous' => $back,
        ], 500 );
    } else {
        return response()->view( 'pages.errors.exception', compact( 'message', 'title', 'back' ), 500 );
    }
} );
