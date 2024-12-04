<?php

namespace App\Console\Commands;

use App\Services\ModulesService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ModuleMailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'modules:mail {namespace} {name} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a module mail class';

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
            $mailsPath = $module['namespace'] . DIRECTORY_SEPARATOR . 'Mail' . DIRECTORY_SEPARATOR;
            $name = ucwords( Str::camel( $this->argument( 'name' ) ) );
            $fileName = $mailsPath . $name;
            $fullRelativePath = 'modules' . DIRECTORY_SEPARATOR . $fileName;
            $namespace = $this->argument( 'namespace' );
            $fileExists = Storage::disk( 'ns-modules' )->exists(
                $fileName . '.php'
            );

            if ( ! $fileExists || ( $fileExists && $this->option( 'force' ) ) ) {
                Storage::disk( 'ns-modules' )->put( $fileName . '.php', view( 'generate.modules.mail', compact(
                    'modules', 'module', 'name', 'namespace'
                ) ) );

                return $this->info( sprintf(
                    __( 'The mail class has been created at the following path "%s"!' ),
                    $fullRelativePath . '.php'
                ) );
            }

            return $this->error( 'The mail class already exists!' );
        }

        return $this->error( 'Unable to locate the module!' );
    }
}
