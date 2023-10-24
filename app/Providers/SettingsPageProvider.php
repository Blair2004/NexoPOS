<?php

namespace App\Providers;

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
        Hook::addFilter( 'ns.settings', function( $class, $identifier ) {
            $classes = Cache::get( 'crud-classes', function() use ( $identifier ) {
                $files = collect( Storage::disk( 'ns' )->files( 'app/Settings' ) );

                return $files->map( fn( $file ) => 'App\Settings\\' . pathinfo( $file )[ 'filename' ] )
                    ->filter( fn( $class ) => ( defined( $class . '::AUTOLOAD' ) && defined( $class . '::IDENTIFIER' ) && $class::IDENTIFIER === $identifier && $class::AUTOLOAD === true ) );
            });

            /**
             * If there is a match, we'll return
             * the first class that matches.
             */
            if ( $classes->isNotEmpty() ) {
                $className  =   $classes->first();

                return new $className;
            }

            return $class;
        }, 10, 2 );
    }
}
