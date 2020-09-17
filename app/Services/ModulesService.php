<?php
namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Illuminate\Support\Arr;
use App\Services\Helper;
use Laravie\Parser\Xml\Document;
use Laravie\Parser\Xml\Reader;
use PhpParser\Error;
use PhpParser\NodeDumper;
use PhpParser\ParserFactory;
use App\Exceptions\CoreException;
use Exception;

class ModulesService
{
    private $modules    =   [];
    private $xmlParser;
    private $options;

    public function __construct()
    {
        if ( ns()->installed() ) {
            /**
             * We can only enable a module if the database is installed.
             */
            $this->options          =   app()->make( Options::class );
        }

        $this->xmlParser    =   new Reader( new Document() );

        Storage::disk( 'ns' )->makeDirectory( 'modules' );
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
            })->map( function( $module ) {
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
                'name'                  =>  [ 'uses'    =>  'name' ]
            ]);

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
                $config[ 'path' ]                       =   $currentModulePath;
                $config[ 'index-file' ]                 =   is_file( $indexPath ) ? $indexPath : false;
                $config[ 'routes-file' ]                =   is_file( $webRoutesPath ) ? $webRoutesPath : false;
                $config[ 'api-file' ]                   =   is_file( $apiRoutesPath ) ? $apiRoutesPath : false;
                $config[ 'controllers-path' ]           =   $currentModulePath . 'Http' . DIRECTORY_SEPARATOR . 'Controllers';
                $config[ 'controllers-relativePath' ]   =   ucwords( $config[ 'namespace' ] ) . DIRECTORY_SEPARATOR . 'Http' . DIRECTORY_SEPARATOR . 'Controllers';
                $config[ 'views-path' ]                 =   $currentModulePath . 'Resources' . DIRECTORY_SEPARATOR . 'Views';
                $config[ 'dashboard-path' ]             =   $currentModulePath . 'Dashboard' . DIRECTORY_SEPARATOR;
                $config[ 'enabled' ]                    =   false; // by default the module is set as disabled
                
                /**
                 * If the system is installed, then we can check if the module is enabled or not
                 * since by default it's not enabled
                 */
                if ( ns()->installed() ) {
                    $modules                =   ( array ) $this->options->get( 'enabled_modules' );
                    $config[ 'migrations' ] =   $this->__getModuleMigration( $config );
                    $config[ 'enabled' ]    =   in_array( $config[ 'namespace' ], $modules ) ? true : false;
                }
                
                /**
                 * Defining Entry Class
                 * Entry class must be namespaced like so : 'Modules\[namespace]\[namespace] . 'Module';
                 */
                $config[ 'entry-class' ]    =  'Modules\\' . $config[ 'namespace' ] . '\\' . $config[ 'namespace' ] . 'Module'; 

                // store providers
                $config[ 'providers' ]      =   [];

                /**
                 * Service providers are registered when the module is enabled
                 */
                if ( $config[ 'enabled' ] ) {

                    /**
                     * Load module folder contents
                     */
                    foreach([ 'Models', 'Services', 'Facades', 'Crud', 'Mails', 'Http', 'Queues', 'Gates', 'Observers', 'Listeners', 'Tests', 'Forms' ] as $folder ) {
                        /**
                         * Load Module models
                         */
                        $files   =   Storage::disk( 'ns-modules' )->allFiles( $config[ 'namespace' ] . DIRECTORY_SEPARATOR . $folder );

                        foreach( $files as $file ) {
                            /**
                             * @todo run service provider
                             */
                            $fileInfo   =   pathinfo(  $modulesPath . $file );

                            if ( $fileInfo[ 'extension' ] == 'php' ) {
                                include_once( base_path( 'modules' ) . DIRECTORY_SEPARATOR . $file );
                            }
                        }
                    }

                    /**
                     * register module service provider
                     */
                    $servicesProviders   =   Storage::disk( 'ns-modules' )->allFiles( $config[ 'namespace' ] . DIRECTORY_SEPARATOR . 'Providers' );

                    foreach( $servicesProviders as $service ) {
                        /**
                         * @todo run service provider
                         */
                        $fileInfo   =   pathinfo( $service );

                        if ( is_file( base_path( 'modules' ) . DIRECTORY_SEPARATOR . $service ) && $fileInfo[ 'extension' ] === 'php' ) {
                            include_once( base_path( 'modules' ) . DIRECTORY_SEPARATOR . $service );
    
                            $className      =   ucwords( $fileInfo[ 'filename' ] );
                            $fullClassName  =   'Modules\\' . $config[ 'namespace' ] . '\\Providers\\' . $className;
    
                            if ( class_exists( $fullClassName ) ) {

                                $config[ 'providers' ][ $className ]   =   new $fullClassName( app() );
                                
                                /**
                                 * If a register method exists
                                 */
                                if ( method_exists( $config[ 'providers' ][ $className ], 'register' ) ) {
                                    call_user_func([ $config[ 'providers' ][ $className ], 'register' ]);
                                }
                            }
                        }
                    }

                    /**
                     * Load Module Config
                     */
                    $files   =   Storage::disk( 'ns-modules' )->allFiles( $config[ 'namespace' ] . DIRECTORY_SEPARATOR . 'Config' );
                    $moduleConfig       =   [];

                    foreach( $files as $file ) {
                        $info               =     pathinfo( $file );
                        $_config            =   include_once( base_path( 'modules' ) . DIRECTORY_SEPARATOR . $file );
                        $final[ $config[ 'namespace' ] ]    =   [];
                        $final[ $config[ 'namespace' ] ][ $info[ 'filename' ] ]     =   $_config;   
                        $moduleConfig       =   Arr::dot( $final );
                    }

                    foreach( $moduleConfig as $key => $value ) {
                        config([ $key => $value ]);
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

    /**
     * Run Modules
     * @return void
     */
    public function init()
    {
        foreach( $this->modules as $module ) {
            if ( ! $module[ 'enabled' ] ) {
                continue;
            }

            /**
             * Autoload Vendors
             */
            if ( is_file( $module[ 'path' ] . DIRECTORY_SEPARATOR .'vendor' . DIRECTORY_SEPARATOR . 'autoload.php' ) ) {
                include_once( $module[ 'path' ] . DIRECTORY_SEPARATOR .'vendor' . DIRECTORY_SEPARATOR . 'autoload.php' );
            }

            /**
             * Run boot() method for each enabled module
             */
            foreach( $module[ 'providers' ] as $provider ) {
                if( method_exists( $provider, 'boot' ) ) {
                    $provider->boot();
                }
            }

            // include module index file
            include_once( $module[ 'index-file' ] );
            
            // run module entry class
            $loadedModule     =   new $module[ 'entry-class' ];

            // add view namespace
            View::addNamespace( ucwords( $module[ 'namespace' ] ), $module[ 'views-path' ] );
        }
    }

    /**
     * Return the list of module as an array
     * @return array of modules
     */
    public function get( $namespace = null )
    {
        if ( $namespace !== null ) {
            return @$this->modules[ $namespace ];
        }
        return $this->modules;
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
        if ( $module  = $this->get( $namespace ) ) {
            $zipFile        =   storage_path() . DIRECTORY_SEPARATOR . 'module.zip';
            // unlink old module zip
            if ( is_file( $zipFile ) ) {
                unlink( $zipFile );
            }

            $moduleDir      =   dirname( $module[ 'index-file' ] );
            $files          =   Storage::disk( 'ns-modules' )->allFiles( ucwords( $namespace ) );

            /**
             * get ignored manifest
             */
            $manifest           =   false;
            if ( Storage::disk( 'ns-modules' )->exists( ucwords( $namespace ) . DIRECTORY_SEPARATOR . 'manifest.json' ) ) {
                $manifest       =   json_decode( Storage::disk( 'ns-modules' )->get( ucwords( $namespace ) . DIRECTORY_SEPARATOR . 'manifest.json' ), true );
            }

            /**
             * if a file is within an ignore 
             * match the looped file, it's skipped
             */
            $files      =   array_values( collect( $files )->filter( function( $file ) use ( $manifest, $namespace ) {
                if ( is_array( @$manifest[ 'ignore' ] ) ) {
                    foreach( $manifest[ 'ignore' ] as $check ) {
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
                    strpos( $file, $namespace . '/.git' ) === false && 
                    strpos( $file, $namespace . '/composer.json' ) ===  false &&
                    strpos( $file, $namespace . '/composer.lock' ) ===  false
                ) {
                    $zipArchive->addFile( base_path() . DIRECTORY_SEPARATOR . $file, substr( $file, strlen( 'modules' . DIRECTORY_SEPARATOR ) ) );
                }
            }

            $zipArchive->close();

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

        $rawFiles    =   Storage::disk( 'ns-modules' )->allFiles( '.temp' . DIRECTORY_SEPARATOR . $fileInfo[ 'filename' ] );
        $module         =   [];

        /**
         * Just retreive the files name
         */
        $files  =   array_map( function( $file ) {
            $info   =   pathinfo( $file );
            return $info[ 'basename' ];
        }, $rawFiles );

        if ( in_array( 'config.xml', $files ) ) {

            
            $file   =   '.temp' . DIRECTORY_SEPARATOR . $fileInfo[ 'filename' ] . DIRECTORY_SEPARATOR .'config.xml';
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

                Storage::disk( 'ns-modules' )->put( 
                    str_replace( '.temp' . DIRECTORY_SEPARATOR, '', $file ),
                    Storage::disk( 'ns-modules' )->get( $file )
                );

                /**
                 * create a symlink directory 
                 * only if the module has that folder
                 */
                $this->createSymLink( $moduleNamespace );
            }

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
                Storage::disk( 'public' )->makeDirectory( 'modules' . DIRECTORY_SEPARATOR . $moduleNamespace );
                $link           =   @\symlink( $target, public_path( '/modules/' . strtolower( $moduleNamespace ) ) );
            } else {
                $mode       =   'J';
                $link       =   public_path( 'modules' . DIRECTORY_SEPARATOR . strtolower( $moduleNamespace ) );
                $target     =   base_path( 'modules' . DIRECTORY_SEPARATOR . $moduleNamespace . DIRECTORY_SEPARATOR . 'Public' );
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
             */
            $this->options->delete( strtolower( $module[ 'namespace' ] ) . '_last_migration' );

            /**
             * Run down method for all migrations 
             */

            $migrationFiles   =   Storage::disk( 'ns-modules' )->allFiles( 
                $module[ 'namespace' ] . DIRECTORY_SEPARATOR . 'Migrations' . DIRECTORY_SEPARATOR
            );

            /**
             * Checks if migration files exists
             * so that we can "down" all migrations
             */
            if ( $migrationFiles ) {
                foreach( $migrationFiles as $file ) {
                    $this->__runSingleFile( 'down', $module, $file );
                }
            }

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
                    'message'   =>  __( 'The migration has been successfully runned' )
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
        // check if module exists
        if ( $module = $this->get( $namespace ) ) {
            // @todo sandbox to test if the module runs
            $enabledModules     =   ( array ) $this->options->get( 'enabled_modules' );

            /**
             * Let's check if that module can be enabled
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
             * We're now atempting to trigger the module
             */
            try {
                include_once( $module[ 'index-file' ] );
                $moduleObject   =   new $module[ 'entry-class' ];
            } catch( Exception $error ) {
                return [
                    'status'    =>  'failed',
                    'message'   =>  $error->getMessage(),
                    'module'    =>  $module
                ];
            }

            // make sure to enable only once
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
        $module         =   $this->get( $namespace );
        
        /**
         * if module exists
         */
        if ( $module ) {
            return $this->__getModuleMigration( $module );
        }

        return [];
    }

    /**
     * get module migration without
     * having the modules array built.
     * @param array module namespace
     * @return array of migration files
     */
    private function __getModuleMigration( $module )
    {
        /**
         * If the last migration is not defined
         * that means we're running it for the first time
         * we'll set the migration to 0.0 then.
         */
        $lastVersion        =   $this->options->get( strtolower( $module[ 'namespace' ] ) . '_last_migration', '0.0.0' );
        $currentVersion     =   $module[ 'version' ];
        $directories        =   Storage::disk( 'ns-modules' )->directories( ucwords( $module[ 'namespace' ] ) . DIRECTORY_SEPARATOR . 'Migrations' . DIRECTORY_SEPARATOR );
        $version_names      =   [];

        foreach( $directories as $dir ) {
            $version        =   basename( $dir );

            /**
             * the last version should be lower than the looped versions
             * the current version should greather or equal to the looped versions
             */
            if ( 
                version_compare( $lastVersion, $version, '<' ) && 
                version_compare( $currentVersion, $version, '>=' )
            ) {					
                $files      =   Storage::disk( 'ns-modules' )->allFiles( 
                    ucwords( $module[ 'namespace' ] ) . DIRECTORY_SEPARATOR . 'Migrations' . DIRECTORY_SEPARATOR . $version 
                );

                /**
                 * add a migration only if there is a file to add.
                 */
                if ( count( $files ) ) {
                    $version_names[ $version ]    =   $files;
                }
            }
        }

        $version_array     =   array_keys( $version_names );

        usort( $version_array, function( $a, $b ) {
            return version_compare( $a, $b, '>' );
        });

        $ordered_versions    =   [];

        foreach( $version_array as $version ) {
            $ordered_versions[ $version ]   =   $version_names[ $version ];
        }
        
        return $ordered_versions;
    }

    /**
     * Run module migration
     * @param string module namespace
     * @param string version number
     * @param string file path
     * @return void
     */
    public function runMigration( $namespace, $version, $file )
    {
        $module     =   $this->get( $namespace );
        $result     =   $this->__runSingleFile( 'up', $module, $file );

        /**
         * save the migration only 
         * if it's successful
         */
        if ( $result[ 'status' ] === 'success' ) {
            $this->options->set( strtolower( $namespace ) . '_last_migration', $version );
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
        $migrations     =   $this->getMigrations( $namespace );
        if ( $migrations && is_array( $migrations ) ) {
            foreach( $migrations as $version => $files ) {
                foreach( $files as $file ) {
                    $this->dropMigration( $namespace, $version, $file );
                }
            }
        }
    }
}