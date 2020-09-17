<?php

namespace App\Providers;

use App\Services\ModulesService;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

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
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        //

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
            // ->middleware('api')
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
            $controllers    =   Storage::disk( 'ns-modules' )->files( $module[ 'controllers-relativePath' ] );

            foreach( $controllers as $controller ) {
                $fileInfo   =   pathinfo(  $controller );
                if ( $fileInfo[ 'extension' ] == 'php' ) {
                    include_once( base_path( 'modules' ) . DIRECTORY_SEPARATOR . $controller );
                }
            }

            // if module has a web route file
            if ( $module[ 'routes-file' ] !== false ) {
                Route::middleware([ 'web', 'ns.installed' ])
                    ->namespace( 'Modules\\' . $module[ 'namespace' ] . '\Http\Controllers' )
                    ->group( $module[ 'routes-file' ] );
                
            }

            if ( $module[ 'api-file' ] !== false ) {
                Route::middleware([ 'ns.installed' ]) // 'ns.cors', 
                    ->namespace( 'Modules\\' . $module[ 'namespace' ] . '\Http\Controllers' )
                    ->group( $module[ 'api-file' ] );
            }
        }
    }
}
