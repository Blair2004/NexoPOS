<?php

namespace App\Console\Commands;

use App\Services\ModulesService;
use App\Services\Options;
use Illuminate\Console\Command;

class ModuleSymlinkCommand extends Command
{
    protected $signature = 'modules:symlink {namespace}';

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
        $optionsService = app()->make( Options::class );

        $module = $moduleService->get( $this->argument( 'namespace' ) );

        if ( $module ) {
            $moduleService->createSymLink( $this->argument( 'namespace' ) );

            return $this->info( sprintf( 'The symbolink directory has been created/refreshed for the module "%s".', $module[ 'name' ] ) );
        }

        $this->error( sprintf( 'Unable to find the module "%s".', $this->argument( 'namespace' ) ) );
    }
}
