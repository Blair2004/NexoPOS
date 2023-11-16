<?php

namespace App\Console\Commands;

use App\Services\ModulesService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ModuleController extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'modules:controller {namespace} {name?} {--resource=} {--delete=} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a module controller.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $modules = app()->make( ModulesService::class );

        /**
         * Check if module is defined
         */
        if ( $module = $modules->get( $this->argument( 'namespace' ) ) ) {
            $controllerPath = $module[ 'namespace' ] . DIRECTORY_SEPARATOR . 'Http' . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR;

            /**
             * delete all module controllers
             */
            if ( $this->option( 'delete' ) == 'all' ) {
                if ( $this->confirm( 'Do you want to delete all controllers ?' ) ) {
                    Storage::disk( 'ns-modules' )->deleteDirectory( $controllerPath );
                    Storage::disk( 'ns-modules' )->MakeDirectory( $controllerPath );

                    return $this->info( 'All controllers has been deleted !' );
                }
            }

            /**
             * Define the file name
             */
            $name = ucwords( Str::camel( $this->argument( 'name' ) ) );
            $fileName = $controllerPath . $name;
            $namespace = $this->argument( 'namespace' );
            $fileExists = Storage::disk( 'ns-modules' )->exists(
                $fileName . '.php'
            );

            if ( ! empty( $name ) ) {
                if ( ! $fileExists || ( $fileExists && $this->option( 'force' ) ) ) {
                    Storage::disk( 'ns-modules' )->put(
                        $fileName . '.php', view( 'generate.modules.controller', compact(
                            'modules', 'module', 'name', 'namespace'
                        ) ) );

                    return $this->info( sprintf(
                        __( 'The controller has been created for the module "%s"!' ),
                        $module[ 'name' ]
                    ) );
                }

                return $this->error( 'The controller already exists !' );
            }

            return $this->error( 'The controller name cannot be empty.' );
        }

        return $this->error( 'Unable to located the module !' );
    }
}
