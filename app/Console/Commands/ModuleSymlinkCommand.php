<?php

namespace App\Console\Commands;

use App\Services\ModulesService;
use Illuminate\Console\Command;

class ModuleSymlinkCommand extends Command
{
    protected $signature = 'modules:symlink {namespace?}';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        /**
         * @var ModulesService
         */
        $moduleService = app()->make( ModulesService::class );

        if ( ! empty( $this->argument( 'namespace' ) ) ) {
            $module = $moduleService->get( $this->argument( 'namespace' ) );

            if ( $module ) {
                $moduleService->createSymLink( $this->argument( 'namespace' ) );

                $this->newLine();

                return $this->info( sprintf( 'The symbolink directory has been created/refreshed for the module "%s".', $module[ 'name' ] ) );
            }

            $this->error( sprintf( 'Unable to find the module "%s".', $this->argument( 'namespace' ) ) );
        } else {
            $modules = $moduleService->get();

            $this->withProgressBar( $modules, function( $module ) use ( $moduleService ) {
                $moduleService->createSymLink( $module[ 'namespace' ] );
            });

            $this->newLine();

            return $this->info( sprintf( 'The symbolink directory has been created/refreshed for "%s" modules.', count( $modules ) ) );
        }
    }
}
