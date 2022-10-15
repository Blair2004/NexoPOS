<?php

namespace App\Console\Commands;

use App\Services\ModulesService;
use Exception;
use Illuminate\Console\Command;

class ModuleEnableCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'modules:enable {identifier}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Will enable a module if it\'s available on the system.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        private ModulesService $modulesService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            $result = $this->modulesService->enable( $this->argument( 'identifier' ) );

            /**
             * we'll configure the response
             * according to the result.
             */
            if ( $result[ 'status' ] === 'success' ) {
                $this->info( $result[ 'message' ] );
            } else {
                $this->error( $result[ 'message' ] );
            }
        } catch ( Exception $exception ) {
            $this->error( $exception->getMessage() );
        }
    }
}
