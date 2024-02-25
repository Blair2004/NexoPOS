<?php

namespace App\Console\Commands;

use App\Services\ModulesService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ModuleCommandGenerator extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'modules:command {namespace} {argument} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a command for a module.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $modules = app()->make( ModulesService::class );

        /**
         * Check if module is defined
         */
        if ( $module = $modules->get( $this->argument( 'namespace' ) ) ) {
            /**
             * Define the file name
             */
            $commandsPath = $module[ 'namespace' ] . DIRECTORY_SEPARATOR . 'Console' . DIRECTORY_SEPARATOR . 'Commands' . DIRECTORY_SEPARATOR;
            $name = ucwords( Str::camel( $this->argument( 'argument' ) ) );
            $fileName = $commandsPath . $name;
            $namespace = $this->argument( 'namespace' );
            $fileExists = Storage::disk( 'ns-modules' )->exists(
                $fileName . '.php'
            );

            if ( ! $fileExists || ( $fileExists && $this->option( 'force' ) ) ) {
                Storage::disk( 'ns-modules' )->put(
                    $fileName . '.php', view( 'generate.modules.command', compact(
                        'modules', 'module', 'name', 'namespace'
                    ) ) );

                return $this->info( sprintf(
                    __( 'The command has been created for the module "%s"!' ),
                    $module[ 'name' ]
                ) );
            }

            return $this->error( sprintf( 'A similar file is already found at the location: %s.', $fileName . '.php' ) );
        }

        return $this->error( 'Unable to located the module !' );
    }
}
