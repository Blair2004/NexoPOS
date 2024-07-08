<?php

use dekor\ArrayToTextTable;
use Illuminate\Http\Request;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;

if ( env( 'APP_DEBUG' ) ) {
    Route::get( '/routes', function () {
        $values = collect( array_values( (array) app( 'router' )->getRoutes() )[1] )->map( function ( RoutingRoute $route ) {
            return [
                'domain' => $route->getDomain(),
                'uri' => $route->uri(),
                'methods' => collect( $route->methods() )->join( ', ' ),
                'name' => $route->getName(),
            ];
        } )->values();

        return ( new ArrayToTextTable( $values->toArray() ) )->render();
    } );

    Route::get( '/exceptions', function ( Request $request ) {
        $class = $request->input( 'class' );
        $exceptions = [
            \App\Exceptions\CoreException::class,
            \App\Exceptions\CoreVersionMismatchException::class,
            \App\Exceptions\MethodNotAllowedHttpException::class,
            \App\Exceptions\MissingDependencyException::class,
            \App\Exceptions\ModuleVersionMismatchException::class,
            \App\Exceptions\NotAllowedException::class,
            \App\Exceptions\NotFoundException::class,
            \App\Exceptions\QueryException::class,
            \App\Exceptions\ValidationException::class,
        ];

        if ( in_array( $class, $exceptions ) ) {
            throw new $class;
        }

        return abort( 404, 'Exception not found.' );
    } );
}
