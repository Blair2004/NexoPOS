<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use App\Services\Options;
use App\Services\DateService;
use App\Services\Modules;
use App\Services\ModulesService;
use Carbon\Carbon;

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
        $moduleService     =   app()->make( ModulesService::class );
        $optionsService    =   app()->make( Options::class );

        $module     =   $moduleService->get( $this->argument( 'namespace' ) );

        if ( $module ) {
            $moduleService->createSymLink( $this->argument( 'namespace' ) );
            return $this->info( sprintf( 'The symbolink directory has been created/refreshed for the module "%s".', $module[ 'name' ] ) );
        }

        $this->error( sprintf( 'Unable to find the module "%s".', $this->argument( 'namespace' ) ) );
    }
}