<?php

namespace App\Classes;

use App\Http\Middleware\CheckApplicationHealthMiddleware;
use App\Http\Middleware\CheckMigrationStatus;
use App\Http\Middleware\InstalledStateMiddleware;
use App\Services\ModulesService;
use Illuminate\Support\Facades\Route;

class ModuleRouting
{
    public static function register()
    {
        /**
         * @var ModulesService $Modules
         */
        $modulesService = app()->make( ModulesService::class );

        foreach ( $modulesService->getEnabledAndAutoloadedModules() as $module ) {
            $domain = pathinfo( env( 'APP_URL' ) );

            /**
             * will load all web.php file as dashboard routes.
             */
            if ( $module[ 'routes-file' ] !== false ) {
                if ( env( 'NS_WILDCARD_ENABLED' ) ) {
                    /**
                     * The defined route should only be applicable
                     * to the main domain.
                     */
                    $domainString = ( $domain[ 'filename' ] ?: 'localhost' ) . ( isset( $domain[ 'extension' ] ) ? '.' . $domain[ 'extension' ] : '' );

                    Route::domain( $domainString )->group( function () use ( $module ) {
                        self::mapModuleWebRoutes( $module );
                    } );
                } else {
                    self::mapModuleWebRoutes( $module );
                }
            }

            /**
             * will load api.php file has api file
             */
            if ( $module[ 'api-file' ] !== false ) {
                if ( env( 'NS_WILDCARD_ENABLED' ) ) {
                    /**
                     * The defined route should only be applicable
                     * to the main domain.
                     */
                    $domainString = ( $domain[ 'filename' ] ?: 'localhost' ) . ( isset( $domain[ 'extension' ] ) ? '.' . $domain[ 'extension' ] : '' );

                    Route::domain( $domainString )->group( function () use ( $module ) {
                        self::mapModuleApiRoutes( $module );
                    } );
                } else {
                    self::mapModuleApiRoutes( $module );
                }
            }
        }
    }

    public static function mapModuleWebRoutes( $module )
    {
        Route::middleware( [
            'web',
            InstalledStateMiddleware::class,
            CheckApplicationHealthMiddleware::class,
            CheckMigrationStatus::class ] )
            ->namespace( 'Modules\\' . $module[ 'namespace' ] . '\Http\Controllers' )
            ->group( $module[ 'routes-file' ] );
    }

    public static function mapModuleApiRoutes( $module )
    {
        Route::prefix( 'api' )
            ->middleware( [ InstalledStateMiddleware::class, 'api' ] )
            ->namespace( 'Modules\\' . $module[ 'namespace' ] . '\Http\Controllers' )
            ->group( $module[ 'api-file' ] );
    }
}
