<?php

namespace App\Console\Commands;

use App\Events\AfterMigrationExecutedEvent;
use App\Exceptions\NotFoundException;
use App\Services\ModulesService;
use Illuminate\Console\Command;

class ModulesMigrateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'modules:migrate {moduleNamespace}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform a module migration for a specific module.';

    /**
     * Create a new command instance.
     *
     * @return void
     */

    /**
     * @var ModulesService
     */
    protected $modulesService;

    public function __construct(
        ModulesService $modulesService
    ) {
        parent::__construct();

        $this->modulesService = $modulesService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $module = $this->modulesService->get( $this->argument( 'moduleNamespace' ) );

        if ( empty( $module ) ) {
            throw new NotFoundException(
                sprintf(
                    __( 'Unable to find a module having the identifier "%s".' ),
                    $this->argument( 'moduleNamespace' )
                )
            );
        }

        $migratedFiles = $this->modulesService->getModuleAlreadyMigratedFiles( $module );
        $unmigratedFiles = $this->modulesService->getModuleUnmigratedFiles( $module, $migratedFiles );

        if ( count( $unmigratedFiles ) === 0 ) {
            return $this->info( sprintf(
                __( 'There is no migrations to perform for the module "%s"' ),
                $module[ 'name' ]
            ) );
        }

        $this->withProgressBar( $unmigratedFiles, function ( $file ) use ( $module ) {
            $response = $this->modulesService->runMigration( $module[ 'namespace' ], $file );
            AfterMigrationExecutedEvent::dispatch( $module, $response, $file );
        } );

        $this->newLine();

        $this->info( sprintf(
            __( 'The module migration has successfully been performed for the module "%s"' ),
            $module[ 'name' ]
        ) );
    }
}
