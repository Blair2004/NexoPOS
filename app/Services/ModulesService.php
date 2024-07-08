<?php

namespace App\Services;

use App\Classes\XMLParser;
use App\Events\ModulesAfterDisabledEvent;
use App\Events\ModulesAfterEnabledEvent;
use App\Events\ModulesAfterRemovedEvent;
use App\Events\ModulesBeforeDisabledEvent;
use App\Events\ModulesBeforeEnabledEvent;
use App\Events\ModulesBeforeRemovedEvent;
use App\Exceptions\MissingDependencyException;
use App\Exceptions\ModuleVersionMismatchException;
use App\Exceptions\NotAllowedException;
use App\Models\ModuleMigration;
use Error as GlobalError;
use Exception;
use Illuminate\Contracts\View\View as ViewView;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use PhpParser\Error;
use PhpParser\ParserFactory;
use SimpleXMLElement;

class ModulesService
{
    private $modules = [];

    private $autoloadedNamespace = [];

    private Options $options;

    const CACHE_MIGRATION_LABEL = 'module-migration-';

    public function __construct()
    {
        if ( Helper::installed() ) {
            /**
             * We can only enable a module if the database is installed.
             */
            $this->options = app()->make( Options::class );

            $this->autoloadedNamespace = explode( ',', env( 'AUTOLOAD_MODULES' ) );
        }

        /**
         * creates the directory modules
         * if that doesn't exists
         */
        if ( ! is_dir( base_path( 'modules' ) ) ) {
            Storage::disk( 'ns' )->makeDirectory( 'modules' );
        }
    }

    /**
     * Will load a set of files within a specifc module.
     */
    public static function loadModuleFile( string $namespace, string $file ): mixed
    {
        $moduleService = app()->make( self::class );
        $module = $moduleService->get( $namespace );
        $filePath = Str::finish( $module[ 'path' ] . $file, '.php' );

        return require $filePath;
    }

    /**
     * Load modules for a defined path.
     *
     * @param string path to load
     */
    public function load( ?string $dir = null ): void
    {
        /**
         * If we're not loading a specific module directory
         */
        if ( $dir == null ) {
            $directories = Storage::disk( 'ns-modules' )->directories();

            /**
             * intersect modules/ and remove it
             * to make sure $this->__init can load successfully.
             */
            collect( $directories )->map( function ( $module ) {
                return str_replace( '/', '\\', $module );
            } )->each( function ( $module ) {
                $this->__init( $module );
            } );
        } else {
            $this->__init( $dir );
        }
    }

    public function resolveRelativePathToClass( $filePath )
    {
        $filePath = str_replace( '/', '\\', $filePath );
        $filePath = str_replace( '.php', '', $filePath );
        $filePath = str_replace( 'modules\\', '', $filePath );

        return 'Modules\\' . $filePath;
    }

    /**
     * Init a module from a provided path.
     */
    public function __init( string $dir ): void
    {
        /**
         * Loading files from module directory
         */
        $rawfiles = Storage::disk( 'ns-modules' )->files( $dir );

        /**
         * Just retrieve the files name
         */
        $files = array_map( function ( $file ) {
            $info = pathinfo( $file );

            return $info[ 'basename' ];
        }, $rawfiles );

        /**
         * Checks if a config file exists
         */
        if ( in_array( 'config.xml', $files ) ) {
            $xmlRelativePath = 'modules' . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR . 'config.xml';
            $xmlConfigPath = base_path() . DIRECTORY_SEPARATOR . $xmlRelativePath;
            $xmlContent = file_get_contents( $xmlConfigPath );

            try {
                $parser = new XMLParser( $xmlConfigPath );
                $config = (array) $parser->getXMLObject();
            } catch ( Exception $exception ) {
                throw new Exception( sprintf(
                    __( 'Failed to parse the configuration file on the following path "%s"' ),
                    $xmlRelativePath
                ) );
            }

            $xmlElement = new \SimpleXMLElement( $xmlContent );

            if ( $xmlElement->core[0] instanceof SimpleXMLElement ) {
                $attributes = $xmlElement->core[0]->attributes();
                $minVersion = 'min-version';
                $maxVersion = 'max-version';

                $config[ 'core' ] = [
                    'min-version' => ( (string) $attributes->$minVersion ) ?? null,
                    'max-version' => ( (string) $attributes->$maxVersion ) ?? null,
                ];
            }

            $config[ 'requires' ] = collect( $xmlElement->children()->requires->xpath( '//dependency' ) )->mapWithKeys( function ( $module ) {
                $module = (array) $module;

                return [
                    $module[ '@attributes' ][ 'namespace' ] => [
                        'min-version' => $module[ '@attributes' ][ 'min-version' ] ?? null,
                        'max-version' => $module[ '@attributes' ][ 'max-version' ] ?? null,
                        'name' => $module[0],
                    ],
                ];
            } )->toArray() ?? [];

            $config[ 'files' ] = $files;

            // If a module has at least a namespace
            if ( $config[ 'namespace' ] !== null ) {
                // index path
                $modulesPath = base_path( 'modules' ) . DIRECTORY_SEPARATOR;
                $currentModulePath = $modulesPath . $dir . DIRECTORY_SEPARATOR;
                $indexPath = $currentModulePath . ucwords( $config[ 'namespace' ] . 'Module.php' );
                $webRoutesPath = $currentModulePath . 'Routes' . DIRECTORY_SEPARATOR . 'web.php';
                $apiRoutesPath = $currentModulePath . 'Routes' . DIRECTORY_SEPARATOR . 'api.php';

                // check index existence
                $config[ 'api-file' ] = is_file( $apiRoutesPath ) ? $apiRoutesPath : false;
                $config[ 'composer-installed' ] = Storage::disk( 'ns-modules' )->exists( $config[ 'namespace' ] . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php' );
                $config[ 'controllers-path' ] = $currentModulePath . 'Http' . DIRECTORY_SEPARATOR . 'Controllers';
                $config[ 'controllers-relativePath' ] = ucwords( $config[ 'namespace' ] ) . DIRECTORY_SEPARATOR . 'Http' . DIRECTORY_SEPARATOR . 'Controllers';
                $config[ 'enabled' ] = false; // by default the module is set as disabled
                $config[ 'has-languages' ] = Storage::disk( 'ns-modules' )->exists( $config[ 'namespace' ] . DIRECTORY_SEPARATOR . 'Lang' );
                $config[ 'lang-relativePath' ] = 'modules' . DIRECTORY_SEPARATOR . ucwords( $config[ 'namespace' ] ) . DIRECTORY_SEPARATOR . 'Lang';
                $config[ 'index-file' ] = is_file( $indexPath ) ? $indexPath : false;
                $config[ 'path' ] = $currentModulePath;
                $config[ 'relativePath' ] = 'modules' . DIRECTORY_SEPARATOR . ucwords( $config[ 'namespace' ] ) . DIRECTORY_SEPARATOR;
                $config[ 'requires-composer' ] = Storage::disk( 'ns-modules' )->exists( $config[ 'namespace' ] . DIRECTORY_SEPARATOR . 'composer.json' ) && ! Storage::disk( 'ns-modules' )->exists( $config[ 'namespace' ] . DIRECTORY_SEPARATOR . '.ignore-composer' );
                $config[ 'routes-file' ] = is_file( $webRoutesPath ) ? $webRoutesPath : false;
                $config[ 'views-path' ] = $currentModulePath . 'Resources' . DIRECTORY_SEPARATOR . 'Views';
                $config[ 'views-relativePath' ] = 'modules' . DIRECTORY_SEPARATOR . ucwords( $config[ 'namespace' ] ) . DIRECTORY_SEPARATOR . 'Views';
                $config[ 'autoloaded' ] = in_array( $config[ 'namespace' ], $this->autoloadedNamespace );

                /**
                 * If the system is installed, then we can check if the module is enabled or not
                 * since by default it's not enabled
                 */
                if ( Helper::installed() ) {
                    $modules = $this->options->get( 'enabled_modules', [] );
                    $config[ 'enabled' ] = in_array( $config[ 'namespace' ], (array) $modules ) ? true : false;
                }

                /**
                 * Defining Entry Class
                 * Entry class must be namespaced like so : 'Modules\[namespace]\[namespace] . 'Module';
                 */
                $config[ 'entry-class' ] = 'Modules\\' . $config[ 'namespace' ] . '\\' . $config[ 'namespace' ] . 'Module';
                $config[ 'providers' ] = $this->getAllValidFiles( Storage::disk( 'ns-modules' )->allFiles( $config[ 'namespace' ] . DIRECTORY_SEPARATOR . 'Providers' ) );
                $config[ 'actions' ] = $this->getAllValidFiles( Storage::disk( 'ns-modules' )->allFiles( $config[ 'namespace' ] . DIRECTORY_SEPARATOR . 'Actions' ) );
                $config[ 'filters' ] = $this->getAllValidFiles( Storage::disk( 'ns-modules' )->allFiles( $config[ 'namespace' ] . DIRECTORY_SEPARATOR . 'Filters' ) );
                $config[ 'commands' ] = collect( Storage::disk( 'ns-modules' )->allFiles( $config[ 'namespace' ] . DIRECTORY_SEPARATOR . 'Console' . DIRECTORY_SEPARATOR . 'Commands' ) )
                    ->mapWithKeys( function ( $file ) {
                        $className = str_replace(
                            ['/', '.php'],
                            ['\\', ''],
                            $file
                        );

                        return [ 'Modules\\' . $className => $file ];
                    } )
                    ->toArray();

                /**
                 * Service providers are registered when the module is enabled
                 */
                if ( $config[ 'enabled' ] ) {
                    /**
                     * Load Module Config
                     */
                    $files = Storage::disk( 'ns-modules' )->allFiles( $config[ 'namespace' ] . DIRECTORY_SEPARATOR . 'Config' );
                    $moduleConfig = [];

                    foreach ( $files as $file ) {
                        $info = pathinfo( $file );
                        $_config = include_once base_path( 'modules' ) . DIRECTORY_SEPARATOR . $file;
                        $final[ $config[ 'namespace' ] ] = [];
                        $final[ $config[ 'namespace' ] ][ $info[ 'filename' ] ] = $_config;
                        $moduleConfig = Arr::dot( $final );
                    }

                    foreach ( $moduleConfig as $key => $value ) {
                        config( [ $key => $value ] );
                    }

                    /**
                     * if the language files are included
                     * we'll add it to the module definition.
                     */
                    $config[ 'langFiles' ] = [];

                    if ( $config[ 'has-languages' ] ) {
                        $rawFiles = Storage::disk( 'ns-modules' )
                            ->allFiles( $config[ 'namespace' ] . DIRECTORY_SEPARATOR . 'Lang' );
                        $rawFiles = $this->getAllValidFiles( $rawFiles, [ 'json' ] );

                        $config[ 'langFiles' ] = collect( $rawFiles )->mapWithKeys( function ( $file ) {
                            $pathInfo = pathinfo( $file );

                            return [ $pathInfo[ 'filename' ] => $file ];
                        } )->toArray();
                    }
                }

                // an index MUST be provided and MUST have the same Name than the module namespace + 'Module'
                if ( $config[ 'index-file' ] ) {
                    $this->modules[ $config[ 'namespace' ] ] = $config;
                }
            }
        } else {
            Log::error( sprintf( __( 'No config.xml has been found on the directory : %s. This folder is ignored' ), $dir ) );
        }
    }

    public function loadModulesMigrations(): void
    {
        $this->modules = collect( $this->modules )->mapWithKeys( function ( $config, $key ) {
            $config[ 'migrations' ] = $this->__getModuleMigration( $config );
            $config[ 'all-migrations' ] = $this->getAllModuleMigrationFiles( $config );

            return [ $key => $config ];
        } )->toArray();
    }

    /**
     * Triggers the module's service provider on the defined method.
     */
    public function triggerServiceProviders( array $config, string $method, string|bool $parentClass = false ): void
    {
        foreach ( $config[ 'providers' ] as $service ) {
            /**
             * @todo run service provider
             */
            $fileInfo = pathinfo( $service );

            if ( is_file( base_path( 'modules' ) . DIRECTORY_SEPARATOR . $service ) && $fileInfo[ 'extension' ] === 'php' ) {
                $className = ucwords( $fileInfo[ 'filename' ] );
                $fullClassName = 'Modules\\' . $config[ 'namespace' ] . '\\Providers\\' . $className;

                if ( class_exists( $fullClassName ) ) {
                    if (
                        ! isset( $config[ 'providers-booted' ] ) ||
                        ! isset( $config[ 'providers-booted' ][ $className ] ) ||
                        $config[ 'providers-booted' ][ $className ]  instanceof $fullClassName
                    ) {
                        $config[ 'providers-booted' ][ $className ] = new $fullClassName( app() );
                    }

                    /**
                     * If a register method exists and the class is an
                     * instance of ModulesServiceProvider
                     */
                    if ( $config[ 'providers-booted' ][ $className ] instanceof $parentClass && method_exists( $config[ 'providers-booted' ][ $className ], $method ) ) {
                        $config[ 'providers-booted' ][ $className ]->$method( $this );
                    }
                }
            }
        }
    }

    /**
     * Will check for a specific module or all the module
     * enabled if there is a dependency error.
     */
    public function dependenciesCheck( ?array $module = null ): void
    {
        if ( $module === null ) {
            collect( $this->getEnabled() )->each( function ( $module ) {
                $this->dependenciesCheck( $module );
            } );
        } else {
            /**
             * We'll check if the requirements
             * are meet for the provided modules
             */
            if ( isset( $module[ 'requires' ] ) ) {
                collect( $module[ 'requires' ] )->each( function ( $dependency, $namespace ) use ( $module ) {
                    if ( $this->get( $namespace ) === null ) {
                        /**
                         * The dependency is missing
                         * let's disable the module
                         */
                        $this->disable( $module[ 'namespace' ] );

                        throw new MissingDependencyException( __(
                            sprintf(
                                __( 'The module "%s" has been disabled as the dependency "%s" is missing. ' ),
                                $module[ 'name' ],
                                $dependency[ 'name' ]
                            )
                        ) );
                    }

                    if ( ! $this->get( $namespace )[ 'enabled' ] ) {
                        /**
                         * The dependency is missing
                         * let's disable the module
                         */
                        $this->disable( $module[ 'namespace' ] );

                        throw new MissingDependencyException( __(
                            sprintf(
                                __( 'The module "%s" has been disabled as the dependency "%s" is not enabled. ' ),
                                $module[ 'name' ],
                                $dependency[ 'name' ]
                            )
                        ) );
                    }

                    if ( ! empty( $dependency[ 'min-version' ] ) && ! version_compare( $this->get( $namespace )[ 'version' ], $dependency[ 'min-version' ], '>=' ) ) {
                        /**
                         * The module is disabled because
                         * the version doesn't match the requirement.
                         */
                        $this->disable( $module[ 'namespace' ] );

                        throw new ModuleVersionMismatchException( __(
                            sprintf(
                                __( 'The module "%s" has been disabled as the dependency "%s" is not on the minimum required version "%s". ' ),
                                $module[ 'name' ],
                                $dependency[ 'name' ],
                                $dependency[ 'min-version' ]
                            )
                        ) );
                    }

                    if ( ! empty( $dependency[ 'max-version' ] ) && ! version_compare( $this->get( $namespace )[ 'version' ], $dependency[ 'max-version' ], '<=' ) ) {
                        /**
                         * The module is disabled because
                         * the version doesn't match the requirement.
                         */
                        $this->disable( $module[ 'namespace' ] );

                        throw new ModuleVersionMismatchException( __(
                            sprintf(
                                __( 'The module "%s" has been disabled as the dependency "%s" is on a version beyond the recommended "%s". ' ),
                                $module[ 'name' ],
                                $dependency[ 'name' ],
                                $dependency[ 'max-version' ]
                            )
                        ) );
                    }
                } );
            }

            /**
             * We'll check if the system is
             * at a compatible version for the module
             */
            if (
                isset( $module[ 'core' ] ) &&
                ! empty( $module[ 'core' ][ 'min-version' ] ) &&
                version_compare(
                    config( 'nexopos.version' ),
                    $module[ 'core' ][ 'min-version' ],
                    '<'
                )
            ) {
                $this->disable( $module[ 'namespace' ] );

                throw new ModuleVersionMismatchException( __(
                    sprintf(
                        __( 'The module "%s" has been disabled as it\'s not compatible with the current version of NexoPOS %s, but requires %s. ' ),
                        $module[ 'name' ],
                        config( 'nexopos.version' ),
                        $module[ 'core' ][ 'min-version' ]
                    )
                ) );
            }
        }
    }

    /**
     * Boot a module if it's enabled.
     */
    public function boot( $module = null ): void
    {
        if ( ! empty( $module ) && ( $module[ 'enabled' ] || $module[ 'autoloaded' ] ) ) {
            $this->__boot( $module );
        } else {
            foreach ( $this->modules as $module ) {
                if ( ! ( $module[ 'enabled' ] || $module[ 'autoloaded' ] ) ) {
                    continue;
                }
                $this->__boot( $module );
            }
        }
    }

    /**
     * Autoload vendors for a defined module.
     */
    private function __boot( $module ): void
    {
        /**
         * Autoload Vendors
         */
        if ( is_file( $module[ 'path' ] . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php' ) ) {
            include_once $module[ 'path' ] . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
        }

        // run module entry class
        new $module[ 'entry-class' ];

        // add view namespace
        View::addNamespace( ucwords( $module[ 'namespace' ] ), $module[ 'views-path' ] );
    }

    /**
     * Return the list of modules as an array
     */
    public function get( $namespace = null ): bool|array
    {
        if ( $namespace !== null ) {
            return $this->modules[ $namespace ] ?? false;
        }

        return $this->modules;
    }

    /**
     * Get a specific module using the provided
     * namespace only if that module is enabled
     */
    public function getIfEnabled( string $namespace ): bool|array
    {
        $module = $this->modules[ $namespace ] ?? false;

        if ( $module ) {
            return $module[ 'enabled' ] === true ? $module : false;
        }

        return $module;
    }

    /**
     * Get all modules that are enabled and all modules
     * that are set to autload without repeating them.
     *
     * @return Collection
     */
    public function getEnabledAndAutoloadedModules()
    {
        /**
         * trigger boot method only for enabled modules
         * service providers that extends ModulesServiceProvider.
         */
        $autoloadedModulesNamespace = [];

        /**
         * We might manually set some module
         * to always autoload, even if it's disabled.
         */
        if ( env( 'AUTOLOAD_MODULES' ) ) {
            $autoloadedModulesNamespace = explode( ',', env( 'AUTOLOAD_MODULES' ) );
            $autoloadedModulesNamespace = collect( $autoloadedModulesNamespace )->filter( function ( $namespace ) {
                $module = $this->get( trim( $namespace ) );

                return empty( $module[ 'requires' ] );
            } )->toArray();
        }

        /**
         * 1 - Get all enabled modules
         * 2 - Filter out the modules that are not autoloaded
         * 3 - Merge the autoloaded modules with the filtered modules
         */
        $result = collect( $this->getEnabled() )
            ->filter( fn( $module ) => ! in_array( $module[ 'namespace' ], $autoloadedModulesNamespace ) )
            ->merge(
                collect( $this->get() )
                    ->filter( fn( $module ) => in_array( $module[ 'namespace' ], $autoloadedModulesNamespace ) )
            );

        return $result;
    }

    /**
     * Returns the list of active module as an array
     */
    public function getEnabled(): array
    {
        return array_filter( $this->modules, function ( $module ) {
            if ( $module[ 'enabled' ] === true ) {
                return $module;
            }
        } );
    }

    /**
     * Returns the list of active module as an array
     */
    public function getDisabled(): array
    {
        return array_filter( $this->modules, function ( $module ) {
            if ( $module[ 'enabled' ] === false ) {
                return $module;
            }
        } );
    }

    /**
     * Get a module using the index-file.
     */
    public function asFile( string $indexFile ): mixed
    {
        foreach ( $this->modules as $module ) {
            if ( $module[ 'index-file' ] == $indexFile ) {
                return $module;
            }
        }
    }

    /**
     * Extracts a module using provided namespace
     */
    public function extract( string $namespace ): array
    {
        $this->checkManagementStatus();

        if ( $module = $this->get( $namespace ) ) {
            $zipFile = storage_path() . DIRECTORY_SEPARATOR . 'module.zip';
            // unlink old module zip
            if ( is_file( $zipFile ) ) {
                unlink( $zipFile );
            }

            $moduleDir = dirname( $module[ 'index-file' ] );

            /**
             * get excluded manifest
             */
            $manifest = false;

            if ( Storage::disk( 'ns-modules' )->exists( ucwords( $namespace ) . DIRECTORY_SEPARATOR . 'manifest.json' ) ) {
                $manifest = json_decode( Storage::disk( 'ns-modules' )->get( ucwords( $namespace ) . DIRECTORY_SEPARATOR . 'manifest.json' ), true );
            }

            /**
             * let's move all te file
             * that are excluded.
             */
            $exclusionFolders = [];

            if ( $manifest && $manifest[ 'exclude' ] ) {
                foreach ( $manifest[ 'exclude' ] as $file ) {
                    $hash = date( 'y' ) . '-' . date( 'm' ) . '-' . date( 'i' ) . '-' . Str::random( 20 );
                    $path = base_path( 'storage/app/' . $hash );
                    $originalPath = $moduleDir . Str::of( $file )->start( '/' );
                    $exclusionFolders[ $originalPath ] = $path;

                    exec( "mkdir $path" );
                    exec( "mv $originalPath/* $path" );
                    exec( "mv $originalPath/.* $path" );
                }
            }

            $files = Storage::disk( 'ns-modules' )->allFiles( ucwords( $namespace ) );

            /**
             * if a file is within an exclude
             * match the looped file, it's skipped
             */
            $files = array_values( collect( $files )->filter( function ( $file ) use ( $manifest, $namespace ) {
                if ( is_array( @$manifest[ 'exclude' ] ) ) {
                    foreach ( $manifest[ 'exclude' ] as $check ) {
                        if ( fnmatch( ucwords( $namespace ) . '/' . $check, $file ) ) {
                            return false;
                        }
                    }
                }

                return true;
            } )->toArray() );

            // create new archive
            $zipArchive = new \ZipArchive;
            $zipArchive->open(
                storage_path() . DIRECTORY_SEPARATOR . 'module.zip',
                \ZipArchive::CREATE |
                \ZipArchive::OVERWRITE
            );
            $zipArchive->addEmptyDir( ucwords( $namespace ) );

            foreach ( $files as $index => $file ) {
                /**
                 * We should avoid to extract git stuff as well
                 */
                if (
                    strpos( $file, $namespace . '/.git' ) === false
                ) {
                    $zipArchive->addFile( base_path( 'modules' ) . DIRECTORY_SEPARATOR . $file, $file );
                }
            }

            $zipArchive->close();

            /**
             * restoring the file & folder that are
             * supposed to be ignored.
             */
            if ( ! empty( $exclusionFolders ) ) {
                foreach ( $exclusionFolders as $destination => $source ) {
                    exec( 'mv ' . $source . '/* ' . $destination );
                    exec( 'mv ' . $source . '/.* ' . $destination );
                    exec( "rm -rf $source" );
                }
            }

            return [
                'path' => $zipFile,
                'module' => $module,
            ];
        }
    }

    /**
     * Uploads a module
     */
    public function upload( UploadedFile $file ): array
    {
        $this->checkManagementStatus();

        $path = $file->store( '', [ 'disk' => 'ns-modules-temp' ] );

        $fileInfo = pathinfo( $file->getClientOriginalName() );
        $fullPath = Storage::disk( 'ns-modules-temp' )->path( $path );
        $extractionFolderName = Str::uuid();
        $dir = dirname( $fullPath );

        $archive = new \ZipArchive;
        $archive->open( $fullPath );
        $archive->extractTo( $dir . DIRECTORY_SEPARATOR . $extractionFolderName );
        $archive->close();

        /**
         * Unlink the uploaded zipfile
         */
        unlink( $fullPath );

        $directory = Storage::disk( 'ns-modules-temp' )->directories( $extractionFolderName );

        if ( count( $directory ) > 1 ) {
            throw new Exception( __( 'Unable to detect the folder from where to perform the installation.' ) );
        }

        $directoryName = pathinfo( $directory[0] )[ 'basename' ];
        $rawFiles = Storage::disk( 'ns-modules-temp' )->allFiles( $extractionFolderName );
        $module = [];

        /**
         * Just retrieve the files name
         */
        $files = array_map( function ( $file ) {
            $info = pathinfo( $file );

            return $info[ 'basename' ];
        }, $rawFiles );

        if ( in_array( 'config.xml', $files ) ) {
            $file = $extractionFolderName . DIRECTORY_SEPARATOR . $directoryName . DIRECTORY_SEPARATOR . 'config.xml';
            $xml = new \SimpleXMLElement(
                Storage::disk( 'ns-modules-temp' )->get( $file )
            );

            if (
                ! isset( $xml->namespace ) ||
                ! isset( $xml->version ) ||
                ! isset( $xml->name ) ||
                $xml->getName() != 'module'
            ) {
                /**
                 * the file send is not a valid module
                 */
                $this->clearTemporaryFiles();

                return [
                    'status' => 'error',
                    'message' => __( 'Invalid Module provided.' ),
                ];
            }

            $moduleNamespace = ucwords( $xml->namespace );
            $moduleVersion = ucwords( $xml->version );

            /**
             * Check if a similar module already exists
             * and if the new module is outdated
             */
            if ( $module = $this->get( $moduleNamespace ) ) {
                if ( version_compare( $module[ 'version' ], $moduleVersion, '>=' ) ) {
                    /**
                     * We're dealing with old module
                     */
                    $this->clearTemporaryFiles();

                    return [
                        'status' => 'danger',
                        'message' => __( 'Unable to upload this module as it\'s older than the version installed' ),
                        'module' => $module,
                    ];
                }

                /**
                 * we need to delete the previous
                 * folder if that folder exists
                 * to avoid keeping unused files.
                 */
                Storage::disk( 'ns-modules' )->deleteDirectory( $moduleNamespace );
            }

            /**
             * @step 1 : creating host folder
             * No errors has been found, We\'ll install the module then
             */
            Storage::disk( 'ns-modules' )->makeDirectory( $moduleNamespace, 0755, true );

            /**
             * @step 2 : move files
             * We're now looping to move files
             * and create symlink for the assets
             */
            foreach ( $rawFiles as $file ) {
                $search = $extractionFolderName . '/' . $directoryName . '/';
                $replacement = $moduleNamespace . DIRECTORY_SEPARATOR;
                $final = str_replace( $search, $replacement, $file );

                Storage::disk( 'ns-modules' )->put(
                    $final,
                    Storage::disk( 'ns-modules-temp' )->get( $file )
                );
            }

            /**
             * clear the migration cache
             *
             * @todo consider clearing the cache for this module
             * whenever an operation changes the module files (update, delete).
             */
            Cache::forget( self::CACHE_MIGRATION_LABEL . $moduleNamespace );

            /**
             * create a symlink directory
             * only if the module has that folder
             */
            $this->createSymLink( $moduleNamespace );

            /**
             * We needs to load all modules, to ensure
             * the new uploaded module is recognized
             */
            $this->load();

            /**
             * @step 3 : run migrations
             * check if the module has a migration
             */
            $this->runAllMigration( $moduleNamespace );

            $module = $this->get( $moduleNamespace );

            $this->clearTemporaryFiles();

            return [
                'status' => 'success',
                'message' => sprintf( __( 'The module was "%s" was successfully installed.' ), $module[ 'name' ] ),
            ];
        } else {
            /**
             * the file send is not a valid module
             */
            $this->clearTemporaryFiles();

            return [
                'status' => 'danger',
                'message' => __( 'The uploaded file is not a valid module.' ),
            ];
        }
    }

    /**
     * Creates a symbolic link to the asset directory
     * for specific module.
     */
    public function createSymLink( string $moduleNamespace ): void
    {
        $this->checkManagementStatus();

        if ( ! is_dir( base_path( 'public/modules' ) ) ) {
            Storage::disk( 'public' )->makeDirectory( 'modules', 0755, true );
        }

        /**
         * checks if a public directory exists and create a
         * link for that directory
         */
        if (
            Storage::disk( 'ns-modules' )->exists( $moduleNamespace . DIRECTORY_SEPARATOR . 'Public' ) &&
            ! is_link( base_path( 'public' ) . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . strtolower( $moduleNamespace ) )
        ) {
            $target = base_path( 'modules/' . $moduleNamespace . '/Public' );

            if ( ! \windows_os() ) {
                $link = @\symlink( $target, public_path( '/modules/' . strtolower( $moduleNamespace ) ) );
            } else {
                $mode = 'J';
                $link = public_path( 'modules' . DIRECTORY_SEPARATOR . strtolower( $moduleNamespace ) );
                $target = base_path( 'modules' . DIRECTORY_SEPARATOR . $moduleNamespace . DIRECTORY_SEPARATOR . 'Public' );
                $link = exec( "mklink /{$mode} \"{$link}\" \"{$target}\"" );
            }
        }

        /**
         * checks if a lang directory exists and create a
         * link for that directory
         */
        if (
            Storage::disk( 'ns-modules' )->exists( $moduleNamespace . DIRECTORY_SEPARATOR . 'Lang' ) &&
            ! is_link( base_path( 'public' ) . DIRECTORY_SEPARATOR . 'modules-lang' . DIRECTORY_SEPARATOR . strtolower( $moduleNamespace ) )
        ) {
            $target = base_path( 'modules/' . $moduleNamespace . '/Lang' );

            if ( ! \windows_os() ) {
                $link = @\symlink( $target, public_path( '/modules-lang/' . strtolower( $moduleNamespace ) ) );
            } else {
                $mode = 'J';
                $link = public_path( 'modules-lang' . DIRECTORY_SEPARATOR . strtolower( $moduleNamespace ) );
                $target = base_path( 'modules' . DIRECTORY_SEPARATOR . $moduleNamespace . DIRECTORY_SEPARATOR . 'Lang' );
                $link = exec( "mklink /{$mode} \"{$link}\" \"{$target}\"" );
            }
        }
    }

    /**
     * Removes a symbolic link created for a module using a namespace
     */
    public function removeSymLink( string $moduleNamespace ): void
    {
        $this->checkManagementStatus();

        $path = base_path( 'public' ) . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $moduleNamespace;

        if ( is_link( $path ) ) {
            unlink( $path );
        }
    }

    /**
     * Checks a module migration
     */
    private function __runModuleMigration( string $moduleNamespace ): array
    {
        /**
         * Load module since it has'nt yet been added to the
         * runtime
         */
        $this->load( $moduleNamespace );

        /**
         * Get the module details
         */
        $module = $this->get( $moduleNamespace );

        /**
         * Run the first migration
         */
        $migrationFiles = $this->getMigrations( $moduleNamespace );

        /**
         * Checks if migration files exists
         */
        if ( count( $migrationFiles ) > 0 ) {
            foreach ( $migrationFiles as $file ) {
                /**
                 * Looping each migration files
                 */
                $this->__runSingleFile( 'up', $module, $file );
            }
        }

        $this->clearTemporaryFiles();

        return [
            'status' => 'success',
            'message' => __( 'The module has been successfully installed.' ),
        ];
    }

    /**
     * Clears Temp Folder
     */
    public function clearTemporaryFiles(): void
    {
        Artisan::call( 'ns:doctor', [ '--clear-modules-temp' => true ] );
    }

    /**
     * Deletes an existing module using the provided namespace
     */
    public function delete( string $namespace ): array
    {
        $this->checkManagementStatus();

        /**
         * Check if module exists first
         */
        if ( $module = $this->get( $namespace ) ) {
            if ( $module[ 'autoloaded' ] ) {
                throw new NotAllowedException( sprintf( __( 'The module "%s" is autoloaded and can\'t be deleted.' ), $module[ 'name' ] ) );
            }

            /**
             * Disable the module first
             */
            $this->disable( $namespace );

            ModulesBeforeRemovedEvent::dispatch( $module );

            /**
             * We revert all migrations made by the modules.
             */
            $this->revertMigrations( $module );

            /**
             * Delete module from filesystem.
             */
            Storage::disk( 'ns-modules' )->deleteDirectory( ucwords( $namespace ) );

            /**
             * remove symlink if that exists
             */
            $this->removeSymLink( $namespace );

            /**
             * unset the module from the
             * array "modules"
             */
            unset( $this->modules[ $namespace ] );

            ModulesAfterRemovedEvent::dispatch( $module );

            return [
                'status' => 'success',
                'code' => 'module_deleted',
                'message' => sprintf( __( 'The modules "%s" was deleted successfully.' ), $module[ 'name' ] ),
                'module' => $module,
            ];
        }

        /**
         * This module can't be found. then return an error
         */
        return [
            'status' => 'danger',
            'message' => sprintf( __( 'Unable to locate a module having as identifier "%s".' ), $namespace ),
            'code' => 'unknow_module',
        ];
    }

    /**
     * Reverts the migrations
     * for a specific module
     */
    public function revertMigrations( array $module, $only = [] ): void
    {
        /**
         * Run down method for all migrations
         */
        $migrationFiles = Storage::disk( 'ns-modules' )->allFiles(
            $module[ 'namespace' ] . DIRECTORY_SEPARATOR . 'Migrations' . DIRECTORY_SEPARATOR
        );

        $migrationFiles = $this->getAllValidFiles( $migrationFiles );

        /**
         * If we would like to revert specific
         * migration, we'll use the $only argument
         */
        if ( ! empty( $only ) ) {
            $migrationFiles = collect( $migrationFiles )->filter( function ( $file ) use ( $only ) {
                return in_array( $file, $only );
            } )->toArray();
        }

        /**
         * Checks if migration files exists
         * so that we can "down" all migrations
         */
        if ( $migrationFiles ) {
            foreach ( $migrationFiles as $file ) {
                $this->__runSingleFile( 'down', $module[ 'namespace' ], $file );
            }
        }
    }

    /**
     * Runs a single file
     */
    private function __runSingleFile( string $method, string $namespace, string $file ): array
    {
        /**
         * include initial migration files
         */
        $filePath = base_path( 'modules' ) . DIRECTORY_SEPARATOR . $file;
        $fileInfo = pathinfo( $filePath );
        $fileName = $fileInfo[ 'filename' ];
        $className = str_replace( ' ', '', ucwords( str_replace( '_', ' ', $fileName ) ) );
        $className = 'Modules\\' . ucwords( $namespace ) . '\Migrations\\' . $className;

        if ( is_file( $filePath ) ) {
            if ( class_exists( $className ) ) {
                return $this->triggerClass( $className, $method );
            } else {
                /**
                 * Includes the migration file which might returns an anonymous
                 * class or a migration class with a defined class.
                 */
                $object = require $filePath;

                if ( is_object( $object ) ) {
                    return $this->triggerObject( $object, $method );
                } else {
                    return $this->triggerClass( $className, $method );
                }
            }

            return [
                'status' => 'error',
                'message' => sprintf( __( 'The migration file doens\'t have a valid class name. Expected class : %s' ), $className ),
            ];
        }

        return [
            'status' => 'error',
            'message' => sprintf( __( 'Unable to locate the following file : %s' ), $filePath ),
        ];
    }

    public function triggerObject( $object, $method )
    {
        /**
         * In case the migration file is an anonymous class,
         * we'll just execute the requested method from the returned object.
         */
        $object->$method();

        return [
            'status' => 'success',
            'message' => __( 'The migration run successfully.' ),
            'data' => [
                'object' => $object,
            ],
        ];
    }

    public function triggerClass( $className, $method )
    {
        /**
         * Create Object
         */
        $object = new $className;

        /**
         * let's try to run a method
         * "up" or "down" and watch for
         * any error.
         */
        if ( ! method_exists( $object, $method ) ) {
            return [
                'status' => 'error',
                'message' => sprintf( __( 'The migration file doens\'t have a valid method name. Expected method : %s' ), $method ),
            ];
        }

        $object->$method();

        return [
            'status' => 'success',
            'message' => __( 'The migration run successfully.' ),
            'data' => [
                'object' => $object,
                'className' => $className,
            ],
        ];
    }

    /**
     * Enables module using a provided namespace
     */
    public function enable( string $namespace ): array|JsonResponse
    {
        $this->checkManagementStatus();

        if ( $module = $this->get( $namespace ) ) {
            if ( $module[ 'autoloaded' ] ) {
                return response()->json( [
                    'status' => 'error',
                    'code' => 'autoloaded_module',
                    'message' => sprintf( __( 'The module "%s" is autoloaded and cannot be enabled.' ), $module[ 'name' ] ),
                ], 403 );
            }

            /**
             * get all the modules that are
             * enabled.
             */
            $enabledModules = $this->options->get( 'enabled_modules', [] );

            ModulesBeforeEnabledEvent::dispatch( $module );

            /**
             * @todo we might need to check if this module
             * has dependencies that are missing.
             */
            try {
                $this->dependenciesCheck( $module );
            } catch ( MissingDependencyException $exception ) {
                if ( $exception instanceof MissingDependencyException ) {
                    if ( count( $module[ 'requires' ] ) === 1 ) {
                        throw new MissingDependencyException(
                            sprintf(
                                __( 'The module %s cannot be enabled as his dependency (%s) is missing or not enabled.' ),
                                $module[ 'name' ],
                                collect( $module[ 'requires' ] )->map( fn( $dep ) => $dep[ 'name' ] )->join( ', ' )
                            )
                        );
                    } else {
                        throw new MissingDependencyException(
                            sprintf(
                                __( 'The module %s cannot be enabled as his dependencies (%s) are missing or not enabled.' ),
                                $module[ 'name' ],
                                collect( $module[ 'requires' ] )->map( fn( $dep ) => $dep[ 'name' ] )->join( ', ' )
                            )
                        );
                    }
                }
            }

            /**
             * Let's check if the main entry file doesn't have an error
             */
            try {
                $code = file_get_contents( $module[ 'index-file' ] );
                $parser = ( new ParserFactory )->createForHostVersion();
                $parser->parse( $code );

                foreach ( $module[ 'providers' ] as $provider ) {
                    $code = file_get_contents( base_path( 'modules' ) . DIRECTORY_SEPARATOR . $provider );
                    $parser = ( new ParserFactory )->createForHostVersion();
                    $parser->parse( $code );
                }
            } catch ( Error $error ) {
                return response()->json( [
                    'status' => 'error',
                    'message' => sprintf(
                        __( 'An Error Occurred "%s": %s' ),
                        $module[ 'name' ],
                        $error->getMessage(),
                    ),
                    'module' => $module,
                ], 502 );
            }

            try {
                /**
                 * We're now atempting to trigger the module.
                 */
                $this->__boot( $module );
                $this->triggerServiceProviders( $module, 'register', ServiceProvider::class );
                $this->triggerServiceProviders( $module, 'boot', ServiceProvider::class );
            } catch ( GlobalError $error ) {
                return response()->json( [
                    'status' => 'error',
                    'message' => sprintf(
                        __( 'An Error Occurred "%s": %s' ),
                        $module[ 'name' ],
                        $error->getMessage(),
                        $error->getFile(),
                    ),
                    'module' => $module,
                ], 502 );
            }

            /**
             * We'll enable the module and make sure it's stored
             * on the option table only once.
             */
            if ( ! in_array( $namespace, $enabledModules ) ) {
                $enabledModules[] = $namespace;
                $this->options->set( 'enabled_modules', $enabledModules );
            }

            /**
             * we might recreate the symlink directory
             * for the module that is about to be enabled
             */
            $this->createSymLink( $namespace );

            ModulesAfterEnabledEvent::dispatch( $module );
            Artisan::call( 'cache:clear' );

            return [
                'status' => 'success',
                'message' => __( 'The module has correctly been enabled.' ),
                'data' => [
                    'code' => 'module_enabled',
                    'module' => $module,
                    'migrations' => $this->getMigrations( $module[ 'namespace' ] ),
                ],
            ];
        }

        return [
            'status' => 'warning',
            'code' => 'unknow_module',
            'message' => __( 'Unable to enable the module.' ),
        ];
    }

    /**
     * Disables Module using a provided namespace
     */
    public function disable( string $namespace ): array
    {
        $this->checkManagementStatus();

        // check if module exists
        if ( $module = $this->get( $namespace ) ) {
            if ( $module[ 'autoloaded' ] ) {
                throw new NotAllowedException( sprintf( __( 'The module "%s" is autoloaded and cannot be disabled.' ), $module[ 'name' ] ) );
            }

            ModulesBeforeDisabledEvent::dispatch( $module );

            // @todo sandbox to test if the module runs
            $enabledModules = $this->options->get( 'enabled_modules', [] );
            $indexToRemove = array_search( $namespace, $enabledModules );

            // if module is found
            if ( $indexToRemove !== false ) {
                unset( $enabledModules[ $indexToRemove ] );
            }

            $this->options->set( 'enabled_modules', $enabledModules );

            ModulesAfterDisabledEvent::dispatch( $module );

            return [
                'status' => 'success',
                'code' => 'module_disabled',
                'message' => __( 'The Module has been disabled.' ),
                'module' => $module,
            ];
        }

        return [
            'status' => 'danger',
            'code' => 'unknow_module',
            'message' => __( 'Unable to disable the module.' ),
        ];
    }

    /**
     * Returns an array with the module migrations.
     */
    public function getMigrations( string $namespace ): array
    {
        $module = $this->get( $namespace );

        if ( $module ) {
            return $this->__getModuleMigration( $module );
        }

        return [];
    }

    public function getAllMigrations( array $module ): array
    {
        $migrations = Storage::disk( 'ns-modules' )
            ->allFiles( ucwords( $module[ 'namespace' ] ) . DIRECTORY_SEPARATOR . 'Migrations' . DIRECTORY_SEPARATOR );

        return $this->getAllValidFiles( $migrations );
    }

    /**
     * Returns the module migrations files
     * that has already been migrated.
     */
    public function getModuleAlreadyMigratedFiles( array $module ): array
    {
        return ModuleMigration::namespace( $module[ 'namespace' ] )
            ->get()
            ->map( fn( $migration ) => $migration->file )
            ->values()
            ->toArray();
    }

    /**
     * Returns the migration without
     * having the modules array built.
     */
    private function __getModuleMigration( array $module, bool $cache = true ): array
    {
        /**
         * If the last migration is not defined
         * that means we're running it for the first time
         * we'll set the migration to 0.0 then.
         */
        $migratedFiles = $cache === true ? Cache::remember( self::CACHE_MIGRATION_LABEL . $module[ 'namespace' ], 3600 * 24, function () use ( $module ) {
            return $this->getModuleAlreadyMigratedFiles( $module );
        } ) : $this->getModuleAlreadyMigratedFiles( $module );

        return $this->getModuleUnmigratedFiles( $module, $migratedFiles );
    }

    /**
     * Returns all migrations file that hasn't
     * yet been runned for a specific module
     */
    public function getModuleUnmigratedFiles( array $module, array $migratedFiles ): array
    {
        $files = $this->getAllModuleMigrationFiles( $module );
        $unmigratedFiles = [];

        foreach ( $files as $file ) {
            /**
             * the last version should be lower than the looped versions
             * the current version should greather or equal to the looped versions
             */
            if ( ! in_array( $file, $migratedFiles ) ) {
                $unmigratedFiles[] = $file;
            }
        }

        /**
         * sort migration so files starting with "Create..." are executed
         * first to avoid updating missing tables.
         */
        sort( $unmigratedFiles );

        return $unmigratedFiles;
    }

    /**
     * Returns all the migration defined
     * for a specific module
     */
    public function getAllModuleMigrationFiles( array $module ): array
    {
        $migrationDirectory = ucwords( $module[ 'namespace' ] ) . DIRECTORY_SEPARATOR . 'Migrations' . DIRECTORY_SEPARATOR;
        $files = Storage::disk( 'ns-modules' )->allFiles( $migrationDirectory );
        $files = $this->getAllValidFiles( $files );

        return collect( $files )->filter( function ( $file ) {
            /**
             * we need to resolve the files and make sure it doesn't have any
             * dependency on a module. For example, we might want a migration from our module to
             * be counted as a migration only if another module exists and is enabled.
             */
            $migration = $this->resolveRelativePathToClass( $file );

            if ( class_exists( $migration ) && defined( $migration . '::DEPENDENCIES' ) ) {
                foreach ( $migration::DEPENDENCIES as $dependency ) {
                    if ( ! $this->getIfEnabled( $dependency ) ) {
                        return false;
                    }
                }
            }

            return true;

        } )->toArray();
    }

    /**
     * Returns files which extension matches
     * the extensions provided.
     */
    private function getAllValidFiles( array $files, $extensions = [ 'php' ] ): array
    {
        /**
         * We only want to restrict file
         * that has the ".php" extension.
         */
        return collect( $files )->filter( function ( $file ) use ( $extensions ) {
            $details = pathinfo( $file );

            return isset( $details[ 'extension' ] ) && in_array( $details[ 'extension' ], $extensions );
        } )->toArray();
    }

    /**
     * Executes module migration.
     */
    public function runMigration( string $namespace, string $file )
    {
        $result = $this->__runSingleFile(
            method: 'up',
            namespace: $namespace,
            file: $file
        );

        /**
         * save the migration only
         * if it's successful
         */
        $migration = ModuleMigration::where( [
            'file' => $file,
            'namespace' => $namespace,
        ] );

        if ( $result[ 'status' ] === 'success' && ! $migration instanceof ModuleMigration ) {
            $migration = new ModuleMigration;
            $migration->namespace = $namespace;
            $migration->file = $file;
            $migration->save();

            /**
             * clear the cache to avoid update loop
             */
            Cache::forget( self::CACHE_MIGRATION_LABEL . $namespace );
        }

        return $result;
    }

    /**
     * Executes all module migrations files.
     */
    public function runAllMigration( string $namespace ): array
    {
        $migrations = $this->getMigrations( $namespace );

        if ( $migrations && is_array( $migrations ) ) {
            foreach ( $migrations as $file ) {
                $this->runMigration( $namespace, $file );
            }
        }

        return [
            'status' => 'success',
            'message' => __( 'All migration were executed.' ),
        ];
    }

    /**
     * Drop Module Migration
     */
    public function dropMigration( string $namespace, string $file ): array
    {
        $module = $this->get( $namespace );

        return $this->__runSingleFile( 'down', $module, $file );
    }

    /**
     * Drop All Migration
     */
    public function dropAllMigrations( string $namespace ): void
    {
        $migrations = $this->getAllMigrations( $this->get( $namespace ) );

        if ( ! empty( $migrations ) ) {
            foreach ( $migrations as $file ) {
                $this->dropMigration( $namespace, $file );
            }
        }
    }

    /**
     * Prevents module management when
     * it's explicitely disabled from the settings
     */
    public function checkManagementStatus(): void
    {
        if ( env( 'NS_LOCK_MODULES', false ) ) {
            throw new NotAllowedException( __( 'Unable to proceed, the modules management is disabled.' ) );
        }
    }

    /**
     * Generate a modules using the
     * configuration provided
     */
    public function generateModule( array $config ): array
    {
        if (
            ! $this->get( $config[ 'namespace' ] ) ||
            ( isset( $config[ 'force' ] ) && $this->get( $config[ 'namespace' ] ) && $config[ 'force' ] )
        ) {
            /**
             * If we decide to overwrite the module
             * we might then consider deleting that already exists
             */
            $folderExists = Storage::disk( 'ns-modules' )->exists( $config[ 'namespace' ] );
            $deleteExisting = isset( $config[ 'force' ] ) && $config[ 'force' ];

            if ( $folderExists && $deleteExisting ) {
                Storage::disk( 'ns-modules' )->deleteDirectory( $config[ 'namespace' ] );
            }

            Storage::disk( 'ns-modules' )->makeDirectory( $config[ 'namespace' ], 0755, true );

            /**
             * Geneate Internal Directories
             */
            foreach ( [ 'Config', 'Crud', 'Events', 'Mails', 'Fields', 'Facades', 'Http', 'Migrations', 'Resources', 'Routes', 'Models', 'Providers', 'Services', 'Public' ] as $folder ) {
                Storage::disk( 'ns-modules' )->makeDirectory( $config[ 'namespace' ] . DIRECTORY_SEPARATOR . $folder, 0755, true );
            }

            /**
             * Generate Sub Folders
             */
            Storage::disk( 'ns-modules' )->makeDirectory( $config[ 'namespace' ] . DIRECTORY_SEPARATOR . 'Http' . DIRECTORY_SEPARATOR . 'Controllers', 0755, true );
            Storage::disk( 'ns-modules' )->makeDirectory( $config[ 'namespace' ] . DIRECTORY_SEPARATOR . 'Migrations', 0755, true );
            Storage::disk( 'ns-modules' )->makeDirectory( $config[ 'namespace' ] . DIRECTORY_SEPARATOR . 'Resources' . DIRECTORY_SEPARATOR . 'Views', 0755, true );

            /**
             * Generate Files
             */
            Storage::disk( 'ns-modules' )->put( $config[ 'namespace' ] . DIRECTORY_SEPARATOR . 'config.xml', $this->streamContent( 'config', $config ) );
            Storage::disk( 'ns-modules' )->put( $config[ 'namespace' ] . DIRECTORY_SEPARATOR . $config[ 'namespace' ] . 'Module.php', $this->streamContent( 'main', $config ) );
            Storage::disk( 'ns-modules' )->put( $config[ 'namespace' ] . DIRECTORY_SEPARATOR . 'Events' . DIRECTORY_SEPARATOR . $config[ 'namespace' ] . 'Event.php', $this->streamContent( 'event', $config ) );
            Storage::disk( 'ns-modules' )->put( $config[ 'namespace' ] . DIRECTORY_SEPARATOR . 'Public' . DIRECTORY_SEPARATOR . 'index.html', '<h1>Silence is golden !</h1>' );
            Storage::disk( 'ns-modules' )->put( $config[ 'namespace' ] . DIRECTORY_SEPARATOR . 'Migrations' . DIRECTORY_SEPARATOR . 'DatabaseMigration.php', View::make( 'generate.modules.migration', [
                'module' => $config,
                'migration' => 'DatabaseMigration',
            ] )->render() );

            /**
             * Generate Module Public Folder
             * create a symbolink link to that directory
             */
            $target = base_path( 'modules/' . $config[ 'namespace' ] . '/Public' );

            if ( ! \windows_os() ) {
                Storage::disk( 'public' )->makeDirectory( 'modules/' . $config[ 'namespace' ], 0755, true );

                $linkPath = public_path( '/modules/' . strtolower( $config[ 'namespace' ] ) );

                if ( ! is_link( $linkPath ) ) {
                    $link = \symlink( $target, $linkPath );
                }
            } else {
                $mode = 'J';
                $link = public_path( 'modules' . DIRECTORY_SEPARATOR . strtolower( $config[ 'namespace' ] ) );
                $target = base_path( 'modules' . DIRECTORY_SEPARATOR . $config[ 'namespace' ] . DIRECTORY_SEPARATOR . 'Public' );
                $link = exec( "mklink /{$mode} \"{$link}\" \"{$target}\"" );
            }

            return [
                'status' => 'success',
                'message' => sprintf( 'Your new module "%s" has been created.', $config[ 'name' ] ),
            ];
        } else {
            throw new NotAllowedException( __( 'A similar module has been found' ) );
        }
    }

    /**
     * Stream Content
     *
     * @return string content
     */
    public function streamContent( string $content, array $config ): ViewView
    {
        switch ( $content ) {
            case 'main':
                return view( 'generate.modules.main', [
                    'module' => $config,
                ] );
                break;
            case 'config':
                return view( 'generate.modules.config', [
                    'module' => $config,
                ] );
                break;
            case 'event':
                return view( 'generate.modules.event', [
                    'module' => $config,
                    'name' => $config[ 'namespace' ] . 'Event',
                ] );
                break;
        }
    }
}
