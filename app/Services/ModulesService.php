<?php
namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Services\Helper;
use Laravie\Parser\Xml\Document;
use Laravie\Parser\Xml\Reader;
use PhpParser\Error;
use PhpParser\ParserFactory;
use App\Exceptions\MissingDependencyException;
use App\Exceptions\ModuleVersionMismatchException;
use App\Exceptions\NotAllowedException;
use App\Models\ModuleMigration;
use Exception;
use SimpleXMLElement;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class ModulesService
{
    private $modules    =   [];
    private $xmlParser;
    private $options;
    private $modulesPath;
    
    const CACHE_MIGRATION_LABEL         =   'module-migration-';

    public function __construct()
    {
        if ( Helper::installed() ) {
            /**
             * We can only enable a module if the database is installed.
             */
            $this->options      =   app()->make( Options::class );
        }
        
        $this->modulesPath      =   base_path( 'modules' ) . DIRECTORY_SEPARATOR;
        $this->xmlParser        =   new Reader( new Document() );

        Storage::disk( 'ns' )->makeDirectory( 'modules' );
    }


    /**
     * Will lot a set of files within a specifc module
     * @param string $module namespace
     * @param string $path to fload
     * @return mixed
     */
    public static function loadModuleFile( $namespace, $file )
    {
        $moduleService      =   app()->make( self::class );
        $module             =   $moduleService->get( $namespace );
        $filePath           =   Str::finish( $module[ 'path' ] . $file, '.php' );
        return require( $filePath );
    }

    /**
     * Load Modules
     * @param string path to load
     * @return void
     */
    public function load( $dir = null )
    {
        /**
         * If we're not loading a specific module directory
         */
        if ( $dir == null ) {
            $directories  =   Storage::disk( 'ns-modules' )->directories();

            /**
             * intersect modules/ and remove it
             * to make sure $this->__init can load successfully.
             */
            collect( $directories )->map( function( $module ) {
                return str_replace( '/', '\\', $module );
            })->each( function( $module ) {
                $this->__init( $module );
            });
        } else {
            $this->__init( $dir );
        }
    }

    /**
     * Init Module directory
     * @param string
     * @return void
     */
    public function __init( $dir ) 
    {
        /**
         * Loading files from module directory
         */
        $rawfiles  =   Storage::disk( 'ns-modules' )->files( $dir );

        /**
         * Just retreive the files name
         */
        $files  =   array_map( function( $file ) {
            $info   =   pathinfo( $file );
            return $info[ 'basename' ];
        }, $rawfiles );

        /**
         * Checks if a config file exists
         */
        if ( in_array( 'config.xml', $files ) ) {
            $xmlContent     =   file_get_contents( base_path() . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR . 'config.xml' );
            $xml            =   $this->xmlParser->extract( $xmlContent );
            $config         =   $xml->parse([
                'namespace'             =>  [ 'uses'    =>  'namespace' ],
                'version'               =>  [ 'uses'    =>  'version' ],
                'author'                =>  [ 'uses'    =>  'author' ],
                'description'           =>  [ 'uses'    =>  'description' ],
                'dependencies'          =>  [ 'uses'    =>  'dependencies' ],
                'name'                  =>  [ 'uses'    =>  'name' ],
                'core'                  =>  [ 'uses'    =>  'core' ],
            ]);

            $xmlElement                 =   new \SimpleXMLElement( $xmlContent );

            if ( $xmlElement->core[0] instanceof SimpleXMLElement ) {
                $attributes     =   $xmlElement->core[0]->attributes();
                $minVersion     =   'min-version';
                $maxVersion     =   'max-version';

                $config[ 'core' ]   =   [
                    'min-version'   =>  ( ( string ) $attributes->$minVersion ) ?? null,
                    'max-version'   =>  ( ( string ) $attributes->$maxVersion ) ?? null,
                ];
            }
            
            $config[ 'requires' ]       =   collect( $xmlElement->children()->requires->xpath( '//dependency' ) )->mapWithKeys( function( $module ) {
                $module     =   ( array ) $module;
                return [
                    $module[ '@attributes' ][ 'namespace' ]     =>  [
                        'min-version'   =>  $module[ '@attributes' ][ 'min-version' ] ?? null,
                        'max-version'   =>  $module[ '@attributes' ][ 'max-version' ] ?? null,
                        'name'          =>  $module[0],
                    ]
                ];
            })->toArray() ?? [];

            $config[ 'files' ]          =   $files;

            // If a module has at least a namespace
            if ( $config[ 'namespace' ] !== null ) {
                // index path
                $modulesPath        =   base_path( 'modules' ) . DIRECTORY_SEPARATOR;
                $currentModulePath  =   $modulesPath . $dir . DIRECTORY_SEPARATOR;
                $indexPath          =   $currentModulePath . ucwords( $config[ 'namespace' ] . 'Module.php' );
                $webRoutesPath      =   $currentModulePath . 'Routes' . DIRECTORY_SEPARATOR . 'web.php';
                $apiRoutesPath      =   $currentModulePath . 'Routes' . DIRECTORY_SEPARATOR . 'api.php';

                // check index existence
                $config[ 'api-file' ]                   =   is_file( $apiRoutesPath ) ? $apiRoutesPath : false;
                $config[ 'composer-installed' ]         =   Storage::disk( 'ns-modules' )->exists( $config[ 'namespace' ] . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php' );
                $config[ 'controllers-path' ]           =   $currentModulePath . 'Http' . DIRECTORY_SEPARATOR . 'Controllers';
                $config[ 'controllers-relativePath' ]   =   ucwords( $config[ 'namespace' ] ) . DIRECTORY_SEPARATOR . 'Http' . DIRECTORY_SEPARATOR . 'Controllers';
                $config[ 'dashboard-path' ]             =   $currentModulePath . 'Dashboard' . DIRECTORY_SEPARATOR;
                $config[ 'enabled' ]                    =   false; // by default the module is set as disabled
                $config[ 'has-languages' ]              =   Storage::disk( 'ns-modules' )->exists( $config[ 'namespace' ] . DIRECTORY_SEPARATOR . 'Lang' );
                $config[ 'lang-relativePath' ]          =   'modules' . DIRECTORY_SEPARATOR . ucwords( $config[ 'namespace' ] ) . DIRECTORY_SEPARATOR . 'Lang';
                $config[ 'index-file' ]                 =   is_file( $indexPath ) ? $indexPath : false;
                $config[ 'path' ]                       =   $currentModulePath;
                $config[ 'relativePath' ]               =   'modules' . DIRECTORY_SEPARATOR . ucwords( $config[ 'namespace' ] ) . DIRECTORY_SEPARATOR;
                $config[ 'requires-composer' ]          =   Storage::disk( 'ns-modules' )->exists( $config[ 'namespace' ] . DIRECTORY_SEPARATOR . 'composer.json' ) && ! Storage::disk( 'ns-modules' )->exists( $config[ 'namespace' ] . DIRECTORY_SEPARATOR . '.ignore-composer' );
                $config[ 'routes-file' ]                =   is_file( $webRoutesPath ) ? $webRoutesPath : false;
                $config[ 'views-path' ]                 =   $currentModulePath . 'Resources' . DIRECTORY_SEPARATOR . 'Views';
                $config[ 'views-relativePath' ]         =   'modules' . DIRECTORY_SEPARATOR . ucwords( $config[ 'namespace' ] ) . DIRECTORY_SEPARATOR . 'Views';
                
                /**
                 * If the system is installed, then we can check if the module is enabled or not
                 * since by default it's not enabled
                 */
                if ( ns()->installed() ) {
                    $modules                        =   ( array ) $this->options->get( 'enabled_modules' );
                    $config[ 'migrations' ]         =   $this->__getModuleMigration( $config );
                    $config[ 'all-migrations' ]     =   $this->getAllModuleMigrationFiles( $config );
                    $config[ 'enabled' ]            =   in_array( $config[ 'namespace' ], $modules ) ? true : false;
                }
                
                /**
                 * Defining Entry Class
                 * Entry class must be namespaced like so : 'Modules\[namespace]\[namespace] . 'Module';
                 */
                $config[ 'entry-class' ]    =  'Modules\\' . $config[ 'namespace' ] . '\\' . $config[ 'namespace' ] . 'Module'; 
                $config[ 'providers' ]      =   $this->getAllValidFiles( Storage::disk( 'ns-modules' )->allFiles( $config[ 'namespace' ] . DIRECTORY_SEPARATOR . 'Providers' ) );
                $config[ 'actions' ]        =   $this->getAllValidFiles( Storage::disk( 'ns-modules' )->allFiles( $config[ 'namespace' ] . DIRECTORY_SEPARATOR . 'Actions' ) );
                $config[ 'filters' ]        =   $this->getAllValidFiles( Storage::disk( 'ns-modules' )->allFiles( $config[ 'namespace' ] . DIRECTORY_SEPARATOR . 'Filters' ) );
                $config[ 'commands' ]       =   collect( Storage::disk( 'ns-modules' )->allFiles( $config[ 'namespace' ] . DIRECTORY_SEPARATOR . 'Console' . DIRECTORY_SEPARATOR . 'Commands' ) )
                    ->mapWithKeys( function( $file ) {
                        $className      =   str_replace(
                            ['/', '.php'],
                            ['\\', ''],
                            $file
                        );
                        return [ 'Modules\\' . $className => $file ];
                    })
                    ->toArray();

                /**
                 * Service providers are registered when the module is enabled
                 */
                if ( $config[ 'enabled' ] ) {

                    /**
                     * Load Module Config
                     */
                    $files   =   Storage::disk( 'ns-modules' )->allFiles( $config[ 'namespace' ] . DIRECTORY_SEPARATOR . 'Config' );
                    $moduleConfig       =   [];

                    foreach( $files as $file ) {
                        $info               =   pathinfo( $file );
                        $_config            =   include_once( base_path( 'modules' ) . DIRECTORY_SEPARATOR . $file );
                        $final[ $config[ 'namespace' ] ]    =   [];
                        $final[ $config[ 'namespace' ] ][ $info[ 'filename' ] ]     =   $_config;   
                        $moduleConfig       =   Arr::dot( $final );
                    }

                    foreach( $moduleConfig as $key => $value ) {
                        config([ $key => $value ]);
                    }

                    /**
                     * if the language files are included
                     * we'll add it to the module definition.
                     */
                    $config[ 'langFiles' ]          =   [];

                    if ( $config[ 'has-languages' ] ) {
                        $rawFiles               =   Storage::disk( 'ns-modules' )
                            ->allFiles( $config[ 'namespace' ] . DIRECTORY_SEPARATOR . 'Lang' );
                        $rawFiles               =   $this->getAllValidFiles( $rawFiles, [ 'json' ] );
                            
                        $config[ 'langFiles' ]  =   collect( $rawFiles )->mapWithKeys( function( $file ) {
                            $pathInfo           =   pathinfo( $file );
                            return [ $pathInfo[ 'filename' ] => $file ];
                        })->toArray();
                    }
                }

                // an index MUST be provided and MUST have the same Name than the module namespace + 'Module'
                if ( $config[ 'index-file' ] ) {
                    $this->modules[ $config[ 'namespace' ] ]    =   $config;
                }
            }

        } else {
            return [
                'status'    =>  'failed',
                'message'   =>  sprintf( __( 'No config.xml has been found on the directory : %s' ), $dir )
            ];
        }
    }

    public function triggerServiceProviders( $config, $method, $parentClass = false )
    {
        foreach( $config[ 'providers' ] as $service ) {
            /**
             * @todo run service provider
             */
            $fileInfo   =   pathinfo( $service );

            if ( is_file( base_path( 'modules' ) . DIRECTORY_SEPARATOR . $service ) && $fileInfo[ 'extension' ] === 'php' ) {

                $className      =   ucwords( $fileInfo[ 'filename' ] );
                $fullClassName  =   'Modules\\' . $config[ 'namespace' ] . '\\Providers\\' . $className;
                
                
                if ( class_exists( $fullClassName ) ) {   
                    if ( 
                        ! isset( $config[ 'providers-booted' ] ) || 
                        ! isset( $config[ 'providers-booted' ][ $className ] ) || 
                        $config[ 'providers-booted' ][ $className ]  instanceof $fullClassName 
                    ) {
                        $config[ 'providers-booted' ][ $className ]   =   new $fullClassName( app() );
                    }
                    
                    /**
                     * If a register method exists and the class is an 
                     * instance of ModulesServiceProvider
                     */
                    if ( $config[ 'providers-booted' ][ $className ] instanceof $parentClass && method_exists( $config[ 'providers-booted' ][ $className ], $method ) ) {
                        call_user_func([ $config[ 'providers-booted' ][ $className ], $method ], $this );
                    }
                }
            }
        }
    }

    /**
     * @deprecated
     */
    public function autoloadModule( $config )
    {
        /**
         * Load module folder contents
         */
        foreach([ 'Models', 'Services', 'Events', 'Facades', 'Crud', 'Mails', 'Http', 'Queues', 'Gates', 'Observers', 'Listeners', 'Tests', 'Forms', 'Settings' ] as $folder ) {
            /**
             * Load all valid files for autoloading.
             */
            $files   =   Storage::disk( 'ns-modules' )->allFiles( $config[ 'namespace' ] . DIRECTORY_SEPARATOR . $folder );

            foreach( $files as $file ) {
                /**
                 * @todo run service provider
                 */
                $fileInfo   =   pathinfo(  $this->modulesPath . $file );

                if ( $fileInfo[ 'extension' ] == 'php' ) {
                    include_once( base_path( 'modules' ) . DIRECTORY_SEPARATOR . $file );
                }
            }
        }
    }

    /**
     * Will check for a specific module or all the module
     * enabled if there is a dependency error.
     * @param null|array $module
     * @return void
     */
    public function dependenciesCheck( $module = null )
    {
        if ( $module === null ) {
            collect( $this->getEnabled() )->each( function( $module ) {
                $this->dependenciesCheck( $module );
            });
        } else {
            /**
             * We'll check if the requirements
             * are meet for the provided modules
             */
            if ( isset( $module[ 'requires' ] ) ) {
                collect( $module[ 'requires' ] )->each( function( $dependency, $namespace ) use ( $module ) {

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
                });
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
     * Run Modules
     * @return void
     */
    public function boot( $module = null )
    {
        if ( ! empty( $module ) && $module[ 'enabled' ] ) {
            $this->__boot( $module );
        } else {
            foreach( $this->modules as $module ) {
                if ( ! $module[ 'enabled' ] ) {
                    continue;
                }
                $this->__boot( $module );
            }
        }
    }

    private function __boot( $module )
    {
        /**
         * Autoload Vendors
         */
        if ( is_file( $module[ 'path' ] . DIRECTORY_SEPARATOR .'vendor' . DIRECTORY_SEPARATOR . 'autoload.php' ) ) {
            include_once( $module[ 'path' ] . DIRECTORY_SEPARATOR .'vendor' . DIRECTORY_SEPARATOR . 'autoload.php' );
        }
        
        // run module entry class
        new $module[ 'entry-class' ];

        // add view namespace
        View::addNamespace( ucwords( $module[ 'namespace' ] ), $module[ 'views-path' ] );
    }

    /**
     * Return the list of module as an array
     * @return array of modules
     */
    public function get($namespace = null)
    {
        if ( $namespace !== null ) {
            return $this->modules[ $namespace ] ?? null;
        }
        
        return $this->modules;
    }

    /**
     * get a specific module using the provided 
     * namespace only if that module is enabled
     * @param string module namespace
     * @return bool|array
     */
    public function getIfEnabled( $namespace )
    {
        $module     =   $this->modules[ $namespace ] ?? false;

        if ( $module ) {
            return $module[ 'enabled' ] === true ? $module : false;
        }

        return $module;
    } 

    /**
     * Return the list of active module as an array
     * @return array of active modules
     */
    public function getEnabled()
    {
        return array_filter( $this->modules, function( $module ) {
            if ( $module[ 'enabled' ] === true ) {
                return $module;
            }
        });
    }

    /**
     * Return the list of active module as an array
     * @return array of active modules
     */
    public function getDisabled()
    {
        return array_filter( $this->modules, function( $module ) {
            if ( $module[ 'enabled' ] === false ) {
                return $module;
            }
        });
    }

    /**
     * Get by File
     * @param string file path
     * @return array/null
     */
    public function asFile( $indexFile )
    {
        foreach( $this->modules as $module ) {
            if ( $module[ 'index-file' ] == $indexFile ) {
                return $module;
            }
        }
    }

    /**
     * Extract module using provided namespace
     * @param string module namespace
     * @return array of module details
     */
    public function extract( $namespace )
    {
        $this->checkManagementStatus();

        if ( $module  = $this->get( $namespace ) ) {
            $zipFile        =   storage_path() . DIRECTORY_SEPARATOR . 'module.zip';
            // unlink old module zip
            if ( is_file( $zipFile ) ) {
                unlink( $zipFile );
            }
            
            $moduleDir      =   dirname( $module[ 'index-file' ] );

            /**
             * get excluded manifest
             */
            $manifest           =   false;

            if ( Storage::disk( 'ns-modules' )->exists( ucwords( $namespace ) . DIRECTORY_SEPARATOR . 'manifest.json' ) ) {
                $manifest       =   json_decode( Storage::disk( 'ns-modules' )->get( ucwords( $namespace ) . DIRECTORY_SEPARATOR . 'manifest.json' ), true );
            }

            /**
             * let's move all te file
             * that are excluded.
             */
            $exclusionFolders  =   [];

            if ( $manifest && $manifest[ 'exclude' ] ) {
                foreach( $manifest[ 'exclude' ] as $file ) {
                    $hash                                   =   date( 'y' ) . '-' . date( 'm' ) . '-' . date( 'i' ) . '-' . Str::random( 20 );
                    $path                                   =   base_path( 'storage/app/' . $hash );
                    $originalPath                           =   $moduleDir . Str::of( $file )->start('/');
                    $exclusionFolders[ $originalPath ]      =   $path;

                    exec( "mkdir $path" );
                    exec( "mv $originalPath/* $path" );
                    exec( "mv $originalPath/.* $path" );
                }
            }

            $files          =   Storage::disk( 'ns-modules' )->allFiles( ucwords( $namespace ) );

            /**
             * if a file is within an exclude 
             * match the looped file, it's skipped
             */

            $files      =   array_values( collect( $files )->filter( function( $file ) use ( $manifest, $namespace ) {
                if ( is_array( @$manifest[ 'exclude' ] ) ) {
                    foreach( $manifest[ 'exclude' ] as $check ) {
                        if ( fnmatch( ucwords( $namespace ) . '/' . $check, $file ) ) {
                            return false;
                        }
                    }
                }

                return true;
                
            })->toArray() );
            
            // create new archive
            $zipArchive     =   new \ZipArchive;
            $zipArchive->open( 
                storage_path() . DIRECTORY_SEPARATOR . 'module.zip', 
                \ZipArchive::CREATE | 
                \ZipArchive::OVERWRITE 
            );
            $zipArchive->addEmptyDir( ucwords( $namespace ) );

            foreach( $files as $index => $file ) {

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
                foreach( $exclusionFolders as $destination => $source ) {
                    exec( 'mv ' . $source . '/* ' . $destination );
                    exec( 'mv ' . $source . '/.* ' . $destination );
                    exec( "rm -rf $source" );
                }
            }

            return [
                'path'      =>  $zipFile,
                'module'    =>  $module
            ];
        }
    }

    /**
     * Upload a module
     * @param File module
     * @return boolean
     */
    public function upload( $file )
    {
        $this->checkManagementStatus();

        if ( ! is_dir( base_path( 'modules' ) . DIRECTORY_SEPARATOR . '.temp' ) ) {
            mkdir( base_path( 'modules' ) . DIRECTORY_SEPARATOR . '.temp' );
        }

        $path   =   Storage::disk( 'ns-modules' )->putFile( 
            '.temp', 
            $file 
        );

        $fileInfo   =   pathinfo( $file->getClientOriginalName() );
        $fullPath   =   base_path( 'modules' ) . DIRECTORY_SEPARATOR . $path;        
        $dir        =   dirname( $fullPath . DIRECTORY_SEPARATOR );

        $archive    =   new \ZipArchive;
        $archive->open( $fullPath );
        $archive->extractTo( $dir . DIRECTORY_SEPARATOR . $fileInfo[ 'filename' ] );
        $archive->close();

        /**
         * Unlink the uploaded zipfile
         */
        unlink( $fullPath );

        $directory  =   Storage::disk( 'ns-modules' )->directories( '.temp' . DIRECTORY_SEPARATOR . $fileInfo[ 'filename' ] );

        if ( count( $directory ) > 1 ) {
            throw new Exception( __( 'Unable to detect the folder from where to perform the installation.' ) );
        }

        $directoryName  =   pathinfo( $directory[0] )[ 'basename' ];
        $rawFiles       =   Storage::disk( 'ns-modules' )->allFiles( '.temp' . DIRECTORY_SEPARATOR . $fileInfo[ 'filename' ] );
        $module         =   [];

        /**
         * Just retreive the files name
         */
        $files  =   array_map( function( $file ) {
            $info   =   pathinfo( $file );
            return $info[ 'basename' ];
        }, $rawFiles );

        if ( in_array( 'config.xml', $files ) ) {

            
            $file   =   '.temp' . DIRECTORY_SEPARATOR . $fileInfo[ 'filename' ] . DIRECTORY_SEPARATOR . $directoryName . DIRECTORY_SEPARATOR . 'config.xml';
            $xml    =   new \SimpleXMLElement( 
                Storage::disk( 'ns-modules' )->get( $file )
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
                $this->__clearTempFolder();
                
                return [ 
                    'status'    =>  'failed',
                    'message'   =>  __( 'Invalid Module provided' )
                ];
            }

            $moduleNamespace    =   ucwords( $xml->namespace );
            $moduleVersion      =   ucwords( $xml->version );

            /**
             * Check if a similar module already exists
             * and if the new module is outdated
             */
            if ( $module = $this->get( $moduleNamespace ) ) {
                
                if ( version_compare( $module[ 'version' ], $moduleVersion, '>=' ) ) {
                    
                    /**
                     * We're dealing with old module
                     */
                    $this->__clearTempFolder();

                    return [
                        'status'    =>  'danger',
                        'message'   =>  __( 'Unable to upload this module as it\'s older than the current on' ),
                        'module'    =>  $module
                    ];
                }
            } 

            /**
             * @step 1 : creating host folder
             * No errors has been found, We\'ll install the module then
             */
            Storage::disk( 'ns-modules' )->makeDirectory( $moduleNamespace );

            /**
             * @step 2 : move files
             * We're now looping to move files
             * and create symlink for the assets
             */

            foreach( $rawFiles as $file ) {

                $replacement    =   str_replace( '.temp' . DIRECTORY_SEPARATOR . $fileInfo[ 'filename' ] . DIRECTORY_SEPARATOR . $directoryName . DIRECTORY_SEPARATOR, $moduleNamespace . DIRECTORY_SEPARATOR, $file );

                Storage::disk( 'ns-modules' )->put( 
                    $replacement,
                    Storage::disk( 'ns-modules' )->get( $file )
                );
            }

            /**
             * clear the migration cache
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
             * @step 3 : run migrations
             * check if the module has a migration
             */                    
            return $this->__runModuleMigration( $moduleNamespace, $xml->version );    

        } else {
            /**
             * the file send is not a valid module
             */
            $this->__clearTempFolder();
            
            return [
                'status'    =>  'danger',
                'message'   =>  __( 'The uploaded file is not a valid module.' ),
            ];
        }
    }

    /**
     * create a symbolink asset directory
     * for specific module
     * @param string module namespace
     * @return void
     */
    public function createSymLink( $moduleNamespace )
    {
        $this->checkManagementStatus();

        Storage::disk( 'public' )->makeDirectory( 'modules' );

        /**
         * checks if a public directory exists and create a 
         * link for that directory
         */
        if ( 
            Storage::disk( 'ns-modules' )->exists( $moduleNamespace . DIRECTORY_SEPARATOR . 'Public' ) && 
            ! is_link( base_path( 'public' ) . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . strtolower( $moduleNamespace ) ) 
        ) {
            $target         =   base_path( 'modules/' . $moduleNamespace . '/Public' );

            if ( ! \windows_os() ) {
                $link           =   @\symlink( $target, public_path( '/modules/' . strtolower( $moduleNamespace ) ) );
            } else {
                $mode       =   'J';
                $link       =   public_path( 'modules' . DIRECTORY_SEPARATOR . strtolower( $moduleNamespace ) );
                $target     =   base_path( 'modules' . DIRECTORY_SEPARATOR . $moduleNamespace . DIRECTORY_SEPARATOR . 'Public' );
                $link       =   exec("mklink /{$mode} \"{$link}\" \"{$target}\"");
            }
        }

        /**
         * checks if a public directory exists and create a 
         * link for that directory
         */
        if ( 
            Storage::disk( 'ns-modules' )->exists( $moduleNamespace . DIRECTORY_SEPARATOR . 'Lang' ) && 
            ! is_link( base_path( 'public' ) . DIRECTORY_SEPARATOR . 'modules-lang' . DIRECTORY_SEPARATOR . strtolower( $moduleNamespace ) ) 
        ) {
            $target         =   base_path( 'modules/' . $moduleNamespace . '/Lang' );

            if ( ! \windows_os() ) {
                $link           =   @\symlink( $target, public_path( '/modules-lang/' . strtolower( $moduleNamespace ) ) );
            } else {
                $mode       =   'J';
                $link       =   public_path( 'modules-lang' . DIRECTORY_SEPARATOR . strtolower( $moduleNamespace ) );
                $target     =   base_path( 'modules' . DIRECTORY_SEPARATOR . $moduleNamespace . DIRECTORY_SEPARATOR . 'Lang' );
                $link       =   exec("mklink /{$mode} \"{$link}\" \"{$target}\"");
            }
        }
    }

    /**
     * remove symlink create for a 
     * module using a namespace
     * @param string module namespace
     * @return void
     */
    public function removeSymLink( $moduleNamespace )
    {
        $this->checkManagementStatus();

        $path       =   base_path( 'public' ) . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $moduleNamespace;

        if ( is_link( $path ) ) {
            unlink( $path );
        }
    }

    /**
     * Check module migration
     * @return array of response
     */
    private function __runModuleMigration( $moduleNamespace, $version )
    {
        $module_version_key     =   strtolower( $moduleNamespace ) . '_last_migration';
            
        if ( $version = $this->options->get( $module_version_key ) != null ) {

            /**
             * the new options will be set after the migration
             */   
            $this->__clearTempFolder();

            return [
                'status'    =>  'success',
                'message'   =>  __( 'A migration is required for this module' ),
                'action'    =>  'migration'
            ];

        } else {

            /**
             * Load module since it has'nt yet been added to the 
             * runtime
             */
            $this->load( $moduleNamespace );

            /**
             * Get the module details
             */
            $module         =   $this->get( $moduleNamespace );

            /**
             * Run the first migration
             */
            $migrationFiles   =   $this->getMigrations( $moduleNamespace );

            /**
             * Checks if migration files exists
             */
            if ( $migrationFiles ) {
                foreach( $migrationFiles as $version => $files ) {

                    /**
                     * Looping each migration files
                     */
                    foreach ( $files as $file ) {
                        $this->__runSingleFile( 'up', $module, $file );
                    }
                    
                }
            }

            $this->options->set( $module_version_key, $version );

            $this->__clearTempFolder();

            return [
                'status'    =>  'success',
                'message'   =>  __( 'The module has been successfully installed.' )
            ];
        }
    }

    /**
     * Clear Temp Folder
     * @return void
     */
    private function __clearTempFolder()
    {
        /**
         * The user may have uploaded some unuseful folders. 
         * We should then delete everything and return an error.
         */

        $directories  =   Storage::disk( 'ns-modules' )->allDirectories( '.temp' );

        foreach( $directories as $directory ) {
            Storage::disk( 'ns-modules' )->deleteDirectory( $directory );
        }

        /**
         * Delete unused files as well
         */
        $files  =   Storage::disk( 'ns-modules' )->allFiles( '.temp' );

        foreach( $files as $file ) {
            Storage::disk( 'ns-modules' )->delete( $file );
        }
    }

    /**
     * delete Modules
     * @param string module namespace
     * @return array of error message
     */
    public function delete( string $namespace )
    {
        $this->checkManagementStatus();

        /**
         * Check if module exists first
         */
        if ( $module = $this->get( $namespace ) ) {
            /**
             * Disable the module first
             */
            $this->disable( $namespace );
            
            /**
             * Delete Migration version
             * @deprecated
             */
            $this->options->delete( strtolower( $module[ 'namespace' ] ) . '_last_migration' );

            $this->revertMigrations( $module );

            /**
             * Delete module from DISK
             */
            Storage::disk( 'ns-modules' )->deleteDirectory( ucwords( $namespace ) );

            /**
             * remove symlink if that exists
             */
            $this->removeSymLink( $namespace );

            return [
                'status'    =>  'success',
                'code'      =>  'module_deleted',
                'module'    =>  $module
            ];
        }

        /**
         * This module can't be found. then return an error
         */
        return [
            'status'    =>  'danger',
            'code'      =>  'unknow_module'
        ];
    }

    /**
     * Will revert the migrations
     * for a specific module
     * @param array $module
     * @return void
     */
    public function revertMigrations( $module, $only = [] )
    {
        /**
         * Run down method for all migrations 
         */

        $migrationFiles   =   Storage::disk( 'ns-modules' )->allFiles( 
            $module[ 'namespace' ] . DIRECTORY_SEPARATOR . 'Migrations' . DIRECTORY_SEPARATOR
        );

        $migrationFiles     =   $this->getAllValidFiles( $migrationFiles );

        /**
         * If we would like to revert specific
         * migration, we'll use the $only argument
         */
        if ( ! empty( $only ) ) {
            $migrationFiles     =   collect( $migrationFiles )->filter( function( $file ) use ( $only ) {
                return in_array( $file, $only );
            })->toArray();
        }

        /**
         * Checks if migration files exists
         * so that we can "down" all migrations
         */
        if ( $migrationFiles ) {
            foreach( $migrationFiles as $file ) {
                $this->__runSingleFile( 'down', $module, $file );
            }
        }
    }

    /**
     * Run a single file
     * @param array module
     * @param string file
     */
    private function __runSingleFile( $method, $module, $file )
    {
        /**
         * include initial migration files
         */             
        $filePath   =   base_path( 'modules' ) . DIRECTORY_SEPARATOR . $file;
        $fileInfo   =   pathinfo( $filePath );
        $fileName   =   $fileInfo[ 'filename' ];
        $className  =   str_replace( ' ', '', ucwords( str_replace( '_', ' ', $fileName ) ) );
        $className  =   'Modules\\' . ucwords( $module[ 'namespace' ] ) . '\Migrations\\' . $className;
        
        if ( is_file( $filePath ) ) {

            /**
             * Include the migration class file
             * and checks if that class exists
             * we're parsin the className from the file name
             */
            include_once( $filePath );
    
            if ( class_exists( $className ) ) {
    
                /**
                 * Create Object
                 */
                $object     =   new $className;
    
                /**
                 * let's try to run a method
                 * "up" or "down" and watch for
                 * any error.
                 */
                $object->$method();

                return [
                    'status'    =>  'success',
                    'message'   =>  __( 'The migration run successfully.' ),
                    'data'      =>  [
                        'object'    =>  $object,
                        'className' =>  $className
                    ]
                ];                
            }

            return [
                'status'    =>  'failed',
                'message'   =>  sprintf( __( 'The migration file doens\'t have a valid class name. Expected class : %s' ), $className )
            ];
        }

        return [
            'status'    =>  'failed',
            'message'   =>  sprintf( __( 'Unable to locate the following file : %s' ), $filePath )
        ];
    }

    /**
     * Enable module
     * @param string namespace
     * @return array of error message
     */
    public function enable( string $namespace )
    {
        $this->checkManagementStatus();

        if ( $module = $this->get( $namespace ) ) {
            /**
             * get all the modules that are 
             * enabled.
             */
            $enabledModules     =   ( array ) $this->options->get( 'enabled_modules' );

            /**
             * @todo we might need to check if this module
             * has dependencies that are missing.
             */
            try {
                $this->dependenciesCheck( $module );
            } catch( MissingDependencyException $exception ) {
                if ( $exception instanceof MissingDependencyException ) {
                    throw new MissingDependencyException( 
                        sprintf( 
                            __( 'The module %s cannot be enabled as his dependencies (%s) are missing or are not enabled.' ),
                            $module[ 'name' ],
                            collect( $module[ 'requires' ])->map( fn( $dep ) => $dep[ 'name' ] )->join( ', ' )
                        )
                    );
                }
            }

            /**
             * Let's check if the main entry file doesn't have an error
             */
            $code       =   file_get_contents( $module[ 'index-file' ] );
            $parser     =   ( new ParserFactory )->create( ParserFactory::PREFER_PHP7 );

            try {
                $attempt  =   $parser->parse( $code );
            } catch ( Error $error ) {
                return [
                    'status'    =>  'failed',
                    'message'   =>  $error->getMessage(),
                    'module'    =>  $module
                ];
            }

            /**
             * We're now atempting to trigger the module.
             */
            $this->__boot( $module );

            /**
             * We'll enable the module and make sure it's stored
             * on the option table only once.
             */
            if ( ! in_array( $namespace, $enabledModules ) ) {
                $enabledModules[]   =   $namespace;
                $this->options->set( 'enabled_modules', json_encode( $enabledModules ) );
            }

            return [
                'status'            =>  'success',
                'message'           =>  __( 'The module has correctly been enabled.' ),
                'data'              =>  [
                    'code'          =>  'module_enabled',
                    'module'        =>  $module,
                    'migrations'    =>  $this->getMigrations( $module[ 'namespace' ] )
                ]
            ];
        }

        return [
            'status'    =>  'warning',
            'code'      =>  'unknow_module',
            'message'   =>  __( 'Unable to enable the module.' ),
        ];
    }

    /**
     * Disable Module
     * @param string module namespace
     * @return array of status message
     */
    public function disable( string $namespace )
    {
        $this->checkManagementStatus();

        // check if module exists
        if ( $module = $this->get( $namespace ) ) {
            // @todo sandbox to test if the module runs
            $enabledModules     =   $this->options->get( 'enabled_modules', []);
            $indexToRemove      =   array_search( $namespace, $enabledModules );

            // if module is found
            if ( $indexToRemove !== false ) {
                unset( $enabledModules[ $indexToRemove ] );
            }

            $this->options->set( 'enabled_modules', json_encode( $enabledModules ) );

            return [
                'status'    =>  'success',
                'code'      =>  'module_disabled',
                'message'   =>  __( 'The Module has been disabled.' ),
                'module'    =>  $module
            ];
        }

        return [
            'status'        =>  'danger',
            'code'          =>  'unknow_module',
            'message'   =>  __( 'Unable to disable the module.' ),
        ];
    }

    /**
     * get Migrations
     * @param string module namespace
     * @return array of version
     */
    public function getMigrations( $namespace )
    {
        $module         =   $this->getIfEnabled( $namespace );
        
        /**
         * if module exists
         */
        if ( $module ) {
            return $this->__getModuleMigration( $module );
        }

        return [];
    }

    public function getAllMigrations( $module )
    {
        $migrations     =   Storage::disk( 'ns-modules' )
            ->allFiles( ucwords( $module[ 'namespace' ] ) . DIRECTORY_SEPARATOR . 'Migrations' . DIRECTORY_SEPARATOR );

        return $this->getAllValidFiles( $migrations );
    }

    /**
     * Will return the module migrations files 
     * that has already been migrated.
     * @param array $module
     * @return array
     */
    public function getModuleAlreadyMigratedFiles( $module )
    {
        return ModuleMigration::namespace( $module[ 'namespace' ] )
            ->get()
            ->map( fn( $migration ) => $migration->file )
            ->values()
            ->toArray();
    }

    /**
     * Get module migration without
     * having the modules array built.
     * @param array module namespace
     * @return array of migration files
     */
    private function __getModuleMigration( $module, $cache = true )
    {
        /**
         * If the last migration is not defined
         * that means we're running it for the first time
         * we'll set the migration to 0.0 then.
         */
        $migratedFiles      =   $cache === true ? Cache::remember( self::CACHE_MIGRATION_LABEL . $module[ 'namespace' ], 3600 * 24, function() use ( $module ) {
            return $this->getModuleAlreadyMigratedFiles( $module );
        }) : $this->getModuleAlreadyMigratedFiles( $module );
            
        return $this->getModuleUnmigratedFiles( $module, $migratedFiles );
    }

    /**
     * Will return all migrations file that hasn't 
     * yet been runned for a specific module
     * @param array $module
     * @param array $migratedFiles
     * @return array
     */
    public function getModuleUnmigratedFiles( $module, $migratedFiles )
    {
        $files              =   $this->getAllModuleMigrationFiles( $module );
        $unmigratedFiles    =   [];

        foreach( $files as $file ) {

            /**
             * the last version should be lower than the looped versions
             * the current version should greather or equal to the looped versions
             */
            if ( ! in_array( $file, $migratedFiles ) ) {
                $unmigratedFiles[]      =       $file;
            }
        }

        return $unmigratedFiles;
    }

    /**
     * Return all the migration defined
     * for a specific module
     * @param array $module
     * @return array
     */
    public function getAllModuleMigrationFiles( $module )
    {
        $files  =   Storage::disk( 'ns-modules' )
            ->allFiles( ucwords( $module[ 'namespace' ] ) . DIRECTORY_SEPARATOR . 'Migrations' . DIRECTORY_SEPARATOR );

        return $this->getAllValidFiles( $files );
    }

    /**
     * Will only return files which extension matches
     * the extensions provided.
     * @param array $files
     * @param array $extensions
     * @return array
     */
    private function getAllValidFiles( $files, $extensions = [ 'php' ] )
    {
        /**
         * We only want to restrict file
         * that has the ".php" extension.
         */
        return collect( $files )->filter( function( $file ) use ( $extensions ) {
            $details    =   pathinfo( $file );
            return isset( $details[ 'extension' ] ) && in_array( $details[ 'extension' ], $extensions );
        })->toArray();
    }

    /**
     * Run module migration
     * @param string module namespace
     * @param string version number
     * @param string file path
     * @return void
     */
    public function runMigration( $namespace, $file )
    {
        $module     =   $this->get( $namespace );
        $result     =   $this->__runSingleFile( 'up', $module, $file );

        /**
         * save the migration only 
         * if it's successful
         */
        $migration       =   ModuleMigration::where([
            'file'          =>  $file,
            'namespace'     =>  $namespace
        ]);

        if ( $result[ 'status' ] === 'success' && ! $migration instanceof ModuleMigration ) {
            $migration              =   new ModuleMigration;
            $migration->namespace   =   $namespace;
            $migration->file        =   $file;
            $migration->save();

            /**
             * clear the cache to avoid update loop
             */
            Cache::forget( self::CACHE_MIGRATION_LABEL . $namespace );
        }

        return $result;
    }

    /**
     * Run all module migration
     * @param string module namespace
     * @param string version number
     * @param string file path
     * @return void
     */
    public function runAllMigration( $namespace, $version, $file )
    {
        $migrations     =   $this->getMigrations( $namespace );
        if ( $migrations && is_array( $migrations ) ) {
            foreach( $migrations as $version => $files ) {
                foreach( $files as $file ) {
                    $this->runMigration( $namespace, $version, $file );
                }
            }
        }
    }

    /**
     * Drop Module Migration
     * @param string module namespace
     */
    public function dropMigration( $namespace, $version, $file )
    {
        $module     =   $this->get( $namespace );
        return $this->__runSingleFile( 'down', $module, $file );
    }

    /**
     * Drop All Migration
     * @param string module namespace
     */
    public function dropAllMigrations( $namespace )
    {
        $migrations     =   $this->getAllMigrations( $namespace );
        if ( ! empty( $migrations ) ) {
            foreach( $migrations as $version => $files ) {
                foreach( $files as $file ) {
                    $this->dropMigration( $namespace, $version, $file );
                }
            }
        }
    }

    /**
     * @deprecated
     */
    public function serviceProvider( $module, $instance, $method, $params = null )
    {
        collect( $module[ 'providers' ] )->each( function( $provider ) use ( $instance, $params, $method ) {
            if ( $provider instanceof $instance ) {
                $provider->$method( $params );
            }
        });
    }

    /**
     * Prevent module management when 
     * it's explicitely disabled from the settings
     * @return void
     */
    public function checkManagementStatus()
    {
        if ( env( 'NS_MODULES_MANAGEMENT_DISABLED', false ) ) {
            throw new NotAllowedException( __( 'Unable to proceed, the modules management is disabled.' ) );
        }
    }
}