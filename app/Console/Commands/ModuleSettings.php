<?php

namespace App\Console\Commands;

use App\Services\ModulesService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ModuleSettings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'modules:settings {namespace} {name} {--force}';

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
        $modules = app()->make( ModulesService::class );

        /**
         * Check if module is defined
         */
        if ( $module = $modules->get( $this->argument( 'namespace' ) ) ) {
            $settingsPath = $module[ 'namespace' ] . DIRECTORY_SEPARATOR . 'Settings' . DIRECTORY_SEPARATOR;

            /**
             * Define the file name
             */
            $name = ucwords( Str::camel( $this->argument( 'name' ) ) );
            $fileName = $settingsPath . $name;
            $namespace = $this->argument( 'namespace' );

            $fileExists = Storage::disk( 'ns-modules' )->exists(
                $fileName . '.php'
            );

            if ( ! $fileExists || ( $fileExists && $this->option( 'force' ) ) ) {
                Storage::disk( 'ns-modules' )->put(
                    $fileName . '.php', view( 'generate.modules.settings', compact(
                        'modules', 'module', 'name', 'namespace'
                    ) ) );

                $relativePath = str_replace( base_path(), '', storage_path( 'modules' ) );

                return $this->info( sprintf(
                    __( 'The settings has been created on path %s' ),
                    '/modules/' . $fileName . '.php'
                ) );
            }

            return $this->error( 'The settings already exists !' );
        }

        return $this->error( 'Unable to located the module !' );
    }
}
