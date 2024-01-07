<?php

namespace App\Console\Commands;

use App\Services\ModulesService;
use Illuminate\Console\Command;

class ModuleDeleteCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'modules:remove {identifier}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Removes an installed module.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle( ModulesService $modulesService )
    {
        $module = $modulesService->get( $this->argument( 'identifier' ) );

        if ( $module !== null && $this->confirm( sprintf( __( 'Would you like to delete "%s"?' ), $module[ 'name' ] ) ) ) {
            $result = $modulesService->delete( $this->argument( 'identifier' ) );

            return match ( $result[ 'status' ] ) {
                'danger' => $this->error( $result[ 'message' ] ),
                'success' => $this->info( $result[ 'message' ] ),
            };
        }

        return $this->error( sprintf( __( 'Unable to find a module having as namespace "%s"' ), $this->argument( 'identifier' ) ) );
    }
}
