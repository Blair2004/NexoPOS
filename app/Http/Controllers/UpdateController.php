<?php

/**
 * NexoPOS Controller
 *
 * @since  1.0
 **/

namespace App\Http\Controllers;

use App\Events\AfterMigrationExecutedEvent;
use App\Services\ModulesService;
use App\Services\UpdateService;
use Illuminate\Http\Request;

class UpdateController extends Controller
{
    public function __construct(
        public ModulesService $modulesService,
        public UpdateService $updateService
    ) {
        // ...
    }

    public function updateDatabase()
    {
        return view( 'pages.database.update', [
            'title' => __( 'Database Update' ),
            'redirect' => session( 'after_update', ns()->route( 'ns.dashboard.home' ) ),
            'modules' => collect( $this->modulesService->getEnabledAndAutoloadedModules() )->filter( fn( $module ) => count( $module[ 'migrations' ] ) > 0 )->toArray(),
        ] );
    }

    public function runMigration( Request $request )
    {
        /**
         * Proceeding code migration.
         */
        if ( $request->input( 'file' ) ) {
            $this->updateService->executeMigrationFromFileName( file: $request->input( 'file' ) );
        }

        /**
         * proceeding the migration for
         * the provided module.
         */
        if ( $request->input( 'module' ) ) {
            $module = $request->input( 'module' );
            foreach ( $module[ 'migrations' ] as $file ) {
                $response = $this->modulesService->runMigration( $module[ 'namespace' ], $file );
                AfterMigrationExecutedEvent::dispatch( $module, $response, $file );
            }
        }

        return [
            'status' => 'success',
            'message' => __( 'The migration has successfully run.' ),
        ];
    }
}
