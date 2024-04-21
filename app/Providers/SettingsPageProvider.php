<?php

namespace App\Providers;

use App\Services\ModulesService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use TorMorten\Eventy\Facades\Events as Hook;

class SettingsPageProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Hook::addFilter( 'ns.settings', function ( $class, $identifier ) {
            $classes = Cache::get( 'crud-classes', function () use ( $identifier ) {
                $files = collect( Storage::disk( 'ns' )->files( 'app/Settings' ) );

                return $files->map( fn( $file ) => 'App\Settings\\' . pathinfo( $file )[ 'filename' ] )
                    ->filter( fn( $class ) => ( defined( $class . '::AUTOLOAD' ) && defined( $class . '::IDENTIFIER' ) && $class::IDENTIFIER === $identifier && $class::AUTOLOAD === true ) );
            } );

            /**
             * If there is a match, we'll return
             * the first class that matches.
             */
            if ( $classes->isNotEmpty() ) {
                $className = $classes->first();

                return new $className;
            }

            /**
             * We'll attempt to perform the same autoload
             * but for only enabled modules
             *
             * @var ModulesService $modulesService
             */
            $modulesService = app()->make( ModulesService::class );

            $classes = collect( $modulesService->getEnabledAndAutoloadedModules() )->map( function ( $module ) use ( $identifier ) {
                $classes = Cache::get( 'modules-crud-classes-' . $module[ 'namespace' ], function () use ( $module ) {
                    $files = collect( Storage::disk( 'ns' )->files( 'modules' . DIRECTORY_SEPARATOR . $module[ 'namespace' ] . DIRECTORY_SEPARATOR . 'Settings' ) );

                    return $files->map( fn( $file ) => 'Modules\\' . $module[ 'namespace' ] . '\Settings\\' . pathinfo( $file )[ 'filename' ] )
                        ->filter( fn( $class ) => ( defined( $class . '::AUTOLOAD' ) && defined( $class . '::IDENTIFIER' ) ) );
                } );

                /**
                 * We pull the cached classes and checks if the
                 * class has autoload and identifier defined.
                 */
                $class = collect( $classes )->filter( fn( $class ) => $class::AUTOLOAD && $class::IDENTIFIER === $identifier );

                if ( $class->count() === 1 ) {
                    return $class->first();
                }

                return false;
            } )->filter();

            /**
             * If there is a match, we'll return
             * the first class that matches.
             */
            if ( $classes->isNotEmpty() ) {
                $className = $classes->first();

                return new $className;
            }

            return $class;
        }, 10, 2 );
    }
}
