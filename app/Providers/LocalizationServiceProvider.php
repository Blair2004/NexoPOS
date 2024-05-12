<?php

namespace App\Providers;

use App\Events\LocaleDefinedEvent;
use App\Services\ModulesService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;

class LocalizationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        Event::listen( function ( LocaleDefinedEvent $event ) {
            $this->loadModuleLocale();
        } );
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    protected function loadModuleLocale()
    {
        $moduleService = app()->make( ModulesService::class );
        $active = $moduleService->getEnabledAndAutoloadedModules();

        foreach ( $active as $module ) {
            if (
                isset( $module[ 'langFiles' ] ) &&
                isset( $module[ 'langFiles' ][ app()->getLocale() ] ) &&
                Storage::disk( 'ns-modules' )->exists( $module[ 'langFiles' ][ app()->getLocale() ] )
            ) {
                $locales = json_decode( file_get_contents( base_path( 'modules' . DIRECTORY_SEPARATOR . $module[ 'langFiles' ][ app()->getLocale() ] ) ), true );
                $newLocales = collect( $locales )->mapWithKeys( function ( $value, $key ) use ( $module ) {
                    $key = $module[ 'namespace' ] . '.' . $key;

                    return [ $key => $value ];
                } )->toArray();

                app( 'translator' )->addLines( $newLocales, app()->getLocale() );
            }
        }
    }
}
