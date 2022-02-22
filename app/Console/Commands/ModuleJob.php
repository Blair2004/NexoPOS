<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Services\Modules;
use App\Services\Setup;
use App\Services\Helper;
use App\Services\ModulesService;

class ModuleJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'modules:job {namespace} {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a module job';

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
            /**
             * Define Variables
             */
            $jobsPath     =   $module[ 'namespace' ] . DIRECTORY_SEPARATOR . 'Jobs' . DIRECTORY_SEPARATOR;
            $name           =   ucwords( Str::camel( $this->argument( 'name' ) ) );
            $fileName       =   $jobsPath . $name;
            $namespace      =   $this->argument( 'namespace' );

            if ( ! Storage::disk( 'ns-modules' )->exists( 
                $fileName 
            ) ) {
                Storage::disk( 'ns-modules' )->put( $fileName . '.php', view( 'generate.modules.job', compact(
                    'modules', 'module', 'name', 'namespace'
                ) ) );
                return $this->info( 'The job has been created !' );
            }      
            return $this->error( 'The job already exists !' );
        }
        return $this->error( 'Unable to locate the module !' );
    }
}
