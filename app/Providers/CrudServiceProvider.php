<?php

namespace App\Providers;

use App\Services\ModulesService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use TorMorten\Eventy\Facades\Events as Hook;

class CrudServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        /**
         * every crud class on the system should be
         * added here in order to be available and supported.
         */
        Hook::addFilter( 'ns-crud-resource', function ( $namespace ) {
            /**
             * We'll attempt autoloading crud that explicitely
             * defined they want to be autoloaded. We expect classes to have 2
             * constant: AUTOLOAD=true, IDENTIFIER=<string>.
             */
            $classes = Cache::get( 'crud-classes', function () {
                $files = collect( Storage::disk( 'ns' )->files( 'app/Crud' ) );

                return $files->map( fn( $file ) => 'App\Crud\\' . pathinfo( $file )[ 'filename' ] )
                    ->filter( fn( $class ) => ( defined( $class . '::AUTOLOAD' ) && defined( $class . '::IDENTIFIER' ) ) );
            } );

            /**
             * We pull the cached classes and checks if the
             * class has autoload and identifier defined.
             */
            $class = collect( $classes )->filter( fn( $class ) => $class::AUTOLOAD && $class::IDENTIFIER === $namespace );

            if ( $class->count() === 1 ) {
                return $class->first();
            }

            /**
             * We'll attempt to perform the same autoload
             * but for only enabled modules
             *
             * @var ModulesService $modulesService
             */
            $modulesService = app()->make( ModulesService::class );

            $classes = collect( $modulesService->getEnabledAndAutoloadedModules() )->map( function ( $module ) use ( $namespace ) {
                $classes = Cache::get( 'modules-crud-classes-' . $module[ 'namespace' ], function () use ( $module ) {
                    $files = collect( Storage::disk( 'ns' )->files( 'modules' . DIRECTORY_SEPARATOR . $module[ 'namespace' ] . DIRECTORY_SEPARATOR . 'Crud' ) );

                    return $files->map( fn( $file ) => 'Modules\\' . $module[ 'namespace' ] . '\Crud\\' . pathinfo( $file )[ 'filename' ] )
                        ->filter( fn( $class ) => ( defined( $class . '::AUTOLOAD' ) && defined( $class . '::IDENTIFIER' ) ) );
                } );

                /**
                 * We pull the cached classes and checks if the
                 * class has autoload and identifier defined.
                 */
                $class = collect( $classes )->filter( fn( $class ) => $class::AUTOLOAD && $class::IDENTIFIER === $namespace );

                if ( $class->count() === 1 ) {
                    return $class->first();
                }

                return false;
            } )->filter();

            /**
             * If the namespace match a module crud instance,
             * we'll use that first result
             */
            if ( $classes->isNotEmpty() ) {
                return $classes->flatten()->first();
            }

            /**
             * We'll still allow users to define crud
             * manually from this section.
             */
            return match ( $namespace ) {
                default => $namespace,
            };
        } );
    }
}
