<?php

namespace App\Console\Commands;

use App\Services\ModulesService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ModuleJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'modules:job {namespace} {name} {--force}';

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
        $modules = app()->make( ModulesService::class );

        /**
         * Check if module is defined
         */
        if ( $module = $modules->get( $this->argument( 'namespace' ) ) ) {
            /**
             * Define Variables
             */
            $jobsPath = $module[ 'namespace' ] . DIRECTORY_SEPARATOR . 'Jobs' . DIRECTORY_SEPARATOR;
            $name = ucwords( Str::camel( $this->argument( 'name' ) ) );
            $fileName = $jobsPath . $name;
            $namespace = $this->argument( 'namespace' );
            $relativePath = 'modules' . DIRECTORY_SEPARATOR . $fileName;

            $fileExists = Storage::disk( 'ns-modules' )->exists(
                $fileName . '.php'
            );

            if ( ! $fileExists || ( $fileExists && $this->option( 'force' ) ) ) {
                Storage::disk( 'ns-modules' )->put( $fileName . '.php', view( 'generate.modules.job', compact(
                    'modules', 'module', 'name', 'namespace'
                ) ) );

                return $this->info(
                    sprintf(
                        'Job %s created successfully in %s',
                        $name,
                        $relativePath . '.php'
                    )
                );
            }

            return $this->error( 'The job already exists !' );
        }

        return $this->error( 'Unable to locate the module !' );
    }
}
