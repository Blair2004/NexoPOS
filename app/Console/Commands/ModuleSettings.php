<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Services\Setup;
use App\Services\Helper;
use App\Services\ModulesService;

class ModuleSettings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'modules:settings {namespace} {name?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create settings for a module.';

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
        $modules    =   app()->make( ModulesService::class );

        /**
         * Check if module is defined
         */
        if ( $module = $modules->get( $this->argument( 'namespace' ) ) ) {

            $settingsPath     =   $module[ 'namespace' ] . DIRECTORY_SEPARATOR . 'Settings' . DIRECTORY_SEPARATOR;

            /**
             * Define the file name
             */
            $name       =   ucwords( Str::camel( $this->argument( 'name' ) ) );
            $fileName   =   $settingsPath . $name;
            $namespace  =   $this->argument( 'namespace' );

            if ( ! empty( $name ) ) {
                if ( ! Storage::disk( 'ns-modules' )->exists( 
                    $fileName 
                ) ) {
                    $path   =   Storage::disk( 'ns-modules' )->put( 
                        $fileName . '.php', view( 'generate.modules.settings', compact(
                        'modules', 'module', 'name', 'namespace'
                    ) ) );
                    return $this->info( 'The settings has been created !' );
                }      
                return $this->error( 'The settings already exists !' );          
            }
            return $this->error( 'The settings name cannot be empty.' );          
        }
        return $this->error( 'Unable to located the module !' );
    }
}
