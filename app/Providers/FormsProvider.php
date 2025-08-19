<?php

namespace App\Providers;

use App\Forms\POSAddressesForm;
use App\Forms\ProcurementForm;
use App\Forms\UserProfileForm;
use App\Services\ModulesService;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionParameter;
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

            if ( class_exists( $field ) ) {
                /**
                 * We'll initialize a reflection class
                 * to perform a verification on the constructor.                                                
                 */
                $reflection = new ReflectionClass( $field );

                if ( $reflection->hasConstant( 'AUTOLOAD' ) && $field::AUTOLOAD && $reflection->hasConstant( 'IDENTIFIER' ) ) {

                    $constructor = $reflection->getConstructor();

                    $params = collect();

                    if ( $constructor ) {
                        $parameters = $constructor ? $constructor->getParameters() : [];
                    
                        $params = collect( $parameters )->map( function( ReflectionParameter $param ) {
                            return [
                                'name' => $param->getName(),
                                'type' => $param->getType() ? $param->getType()->getName() : null,
                                'isOptional' => $param->isOptional(),
                                'isBuiltin' => $param->getType()->isBuiltin(),
                                'default' => $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null,
                            ];
                        });
                    }

                    /**
                     * While loading the relevant field class, we'll attempt to resolve it's dependencies
                     * especially if those are subchild of the Illuminate\Database\Eloquent\Model::class
                     */
                    Hook::addFilter( 'ns.fields', function ( $identifier, $resource = null ) use ( $field, $params ) {
                        if ( $identifier === $field::IDENTIFIER ) {
                            $resolved = collect( $params )->map( function( $param ) use ( $resource, $field ) {
                                $isBuiltin = $param[ 'isBuiltin' ];
        
                                /**
                                 * We strickly want to integrate a D.I of models
                                 * other non-builtin will be resolved using app()->make().
                                 */
                                if ( ! $isBuiltin ) {
                                    if ( is_subclass_of( $param[ 'type' ], Model::class ) ) {
                                        $model = $param[ 'type' ];
                                        $instance = $model::find( $resource );
        
                                        /**
                                         * if the param is not optional, we must have a valid instance.
                                         */
                                        if ( ! $instance instanceof $model && ! $param[ 'isOptional' ] ) {
                                            throw new Exception( sprintf(
                                                __( 'Unable to resolve the dependency %s (%s) for the class %s' ),
                                                $resource,
                                                $model,
                                                $field
                                            ) );
                                        }
        
                                        return $instance;
                                    } else {
                                        return app()->make( $param[ 'type' ] );
                                    }
                                }

                                return false;
                            })->filter();

                            /**
                             * If no dependencies were resolved, we can create a new instance
                             * of the field class.
                             */
                            if ( $resolved->isEmpty() ) {
                                return new $field;
                            }

                            return call_user_func_array( [ $field, '__construct' ], $resolved->toArray() );
                        }

                        return $identifier;
                    });
                }
            }
        }
    }
}
