<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ModulesService;
use Exception;

class ModuleDisableCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'modules:disable {identifier}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Will disable a module if it\'s available on the system.';

    /**
     * Modules Service
     * @var ModulesService $modulesService
     */
    private $modulesService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        ModulesService $modulesService
    )
    {
        parent::__construct();

        $this->modulesService   =   $modulesService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            $result     =   $this->modulesService->disable( $this->argument( 'identifier' ) );
            
            /**
             * we'll configure the response
             * according to the result.
             */
            if ( $result[ 'status' ] === 'success' ) {
                $this->info( $result[ 'message' ] );
            } else {
                $this->error( $result[ 'message' ] );
            }

        } catch( Exception $exception ) {
            $this->error( $exception->getMessage() );
        }
    }
}
