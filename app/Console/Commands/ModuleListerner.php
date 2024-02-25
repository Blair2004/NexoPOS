<?php

namespace App\Console\Commands;

use App\Services\ModulesService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ModuleListerner extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'modules:listener {namespace} {name} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a module event listerner.';

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
            $listenerPath = $module[ 'namespace' ] . DIRECTORY_SEPARATOR . 'Listeners' . DIRECTORY_SEPARATOR;
            $name = ucwords( Str::camel( $this->argument( 'name' ) ) );
            $fileName = $listenerPath . $name;
            $namespace = $this->argument( 'namespace' );
            $relativePath = 'modules' . DIRECTORY_SEPARATOR . $fileName;

            $fileExists = Storage::disk( 'ns-modules' )->exists(
                $fileName . '.php'
            );

            if ( ! $fileExists || ( $fileExists && $this->option( 'force' ) ) ) {
                Storage::disk( 'ns-modules' )->put( $fileName . '.php', view( 'generate.modules.listener', compact(
                    'modules', 'module', 'name', 'namespace'
                ) ) );

                return $this->info( sprintf(
                    __( 'The listener has been created on the path "%s"!' ),
                    $relativePath . '.php'
                ) );
            }

            return $this->error( 'The listener already exists !' );
        }

        return $this->error( 'Unable to locate the module !' );
    }
}
