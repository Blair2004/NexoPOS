<?php

use App\Exceptions\CoreException;
use App\Exceptions\CoreVersionMismatchException;
use App\Exceptions\MethodNotAllowedHttpException;
use App\Exceptions\MissingDependencyException;
use App\Exceptions\ModuleVersionMismatchException;
use App\Exceptions\NotAllowedException;
use App\Exceptions\NotFoundException;
use App\Exceptions\QueryException;
use App\Exceptions\ValidationException;
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
            CoreException::class,
            CoreVersionMismatchException::class,
            MethodNotAllowedHttpException::class,
            MissingDependencyException::class,
            ModuleVersionMismatchException::class,
            NotAllowedException::class,
            NotFoundException::class,
            QueryException::class,
            ValidationException::class,
        ];

        if ( in_array( $class, $exceptions ) ) {
            throw new $class;
        }

        return abort( 404, 'Exception not found.' );
    } );
}
