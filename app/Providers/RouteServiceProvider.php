<?php

namespace App\Providers;

use App\Http\Middleware\CheckMigrationStatus;
use App\Services\ModulesService;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'App\Http\Controllers';

    /**
     * The path to the "home" route for your application.
     *
     * @var string
     */
    public const HOME = '/';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        if ( request()->header( 'x-forwarded-proto' ) === 'https' ) {
            URL::forceScheme( 'https' );
        }

        parent::boot();
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        $this->mapApiRoutes();

        $this->mapWebRoutes();

        $this->mapModulesRoutes();
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        Route::middleware('web')
            ->namespace($this->namespace)
            ->group(base_path('routes/web.php'));
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        Route::prefix('api')
            ->middleware('api')
            ->namespace($this->namespace)
            ->group(base_path('routes/api.php'));
    }

    /**
     * Map module web defined route
     * 
     * follow the same rules applied to self::mapWebRoutes();
     * 
     * @return void
     */
    protected function mapModulesRoutes()
    {
        // make module class
        $Modules    =   app()->make( ModulesService::class );

        foreach( $Modules->getEnabled() as $module ) {

            /**
             * We might check if the module is active
             */

            // include module controllers
            /**
             * @deprecated this inclusion seems useless now
             */
            $controllers    =   Storage::disk( 'ns-modules' )->files( $module[ 'controllers-relativePath' ] );

            foreach( $controllers as $controller ) {
                $fileInfo   =   pathinfo(  $controller );
                if ( $fileInfo[ 'extension' ] == 'php' ) {
                    include_once( base_path( 'modules' ) . DIRECTORY_SEPARATOR . $controller );
                }
            }

            $domain     =   pathinfo( env( 'APP_URL' ) );

            /**
             * will load all web.php file as dashboard routes.
             */
            if ( $module[ 'routes-file' ] !== false ) {
                if ( env( 'NS_WILDCARD_ENABLED' ) ) {
                    /**
                     * The defined route should only be applicable
                     * to the main domain.
                     */
                    $domainString   =   ( $domain[ 'filename' ] ?: 'localhost' ) . ( isset( $domain[ 'extension' ] ) ? '.' . $domain[ 'extension' ] : '' );

                    Route::domain( $domainString )->group( function() use ( $module ) {
                        $this->mapModuleWebRoutes( $module );
                    });
                } else {
                    $this->mapModuleWebRoutes( $module );
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
                    $domainString   =   ( $domain[ 'filename' ] ?: 'localhost' ) . ( isset( $domain[ 'extension' ] ) ? '.' . $domain[ 'extension' ] : '' );

                    Route::domain( $domainString )->group( function() use ( $module ) {
                        $this->mapModuleApiRoutes( $module );
                    });
                } else {
                    $this->mapModuleApiRoutes( $module );
                }
            }
        }
    }

    public function mapModuleWebRoutes( $module )
    {
        Route::middleware([ 'web', 'ns.installed', 'ns.check-application-health', CheckMigrationStatus::class ])
            ->namespace( 'Modules\\' . $module[ 'namespace' ] . '\Http\Controllers' )
            ->group( $module[ 'routes-file' ] );
    }

    public function mapModuleApiRoutes( $module )
    {
        Route::prefix( 'api/nexopos/v4' )
                    ->middleware([ 'ns.installed', 'api' ])
                    ->namespace( 'Modules\\' . $module[ 'namespace' ] . '\Http\Controllers' )
                    ->group( $module[ 'api-file' ] );
    }
}
