<?php

namespace App\Providers;

use App\Forms\POSAddressesForm;
use App\Forms\ProcurementForm;
use App\Forms\UserProfileForm;
use App\Services\ModulesService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use ReflectionClass;
use TorMorten\Eventy\Facades\Events as Hook;

class FormsProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // ...
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Hook::addFilter( 'ns.forms', function ( $class, $identifier ) {
            switch ( $identifier ) {
                case 'ns.user-profile':
                    return new UserProfileForm;
                    break;
                case 'ns.procurement':
                    return new ProcurementForm;
                    break;
                case 'ns.pos-addresses':
                    return new POSAddressesForm;
                    break;
            }

            return $class;
        }, 10, 2 );

        /**
         * We'll scan the fields directory
         * and autoload the fields that has "AUTOLOAD" constant
         * set to true
         */
        $this->autoloadFields(
            path: app_path( 'Fields' ),
            classRoot: 'App\\Fields\\'
        );

        /**
         * Now for all the modules that are enabled we'll make sure
         * to load their fields if they are set to be autoloaded
         *
         * @var ModulesService
         */
        $moduleService = app()->make( ModulesService::class );

        $moduleService->getEnabledAndAutoloadedModules()->each( function ( $module ) {
            $module = (object) $module;
            $this->autoloadFields(
                path: Str::finish( $module->path, DIRECTORY_SEPARATOR ) . 'Fields',
                classRoot: 'Modules\\' . $module->namespace . '\\Fields\\'
            );
        } );
    }

    private function autoloadFields( $path, $classRoot )
    {
        if ( ! is_dir( $path ) ) {
            return;
        }

        $fields = scandir( $path );

        foreach ( $fields as $field ) {
            if ( in_array( $field, [ '.', '..' ] ) ) {
                continue;
            }

            $field = str_replace( '.php', '', $field );
            $field = $classRoot . $field;

            $reflection = new ReflectionClass( $field );

            if ( class_exists( $field ) && $reflection->hasConstant( 'AUTOLOAD' ) && $field::AUTOLOAD && $reflection->hasConstant( 'IDENTIFIER' ) ) {
                Hook::addFilter( 'ns.fields', function ( $identifier ) use ( $field ) {
                    if ( $identifier === $field::IDENTIFIER ) {
                        return new $field;
                    }

                    return $identifier;
                } );
            }
        }
    }
}
