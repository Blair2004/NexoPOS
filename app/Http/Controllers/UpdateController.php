<?php

/**
 * NexoPOS Controller
 * @since  1.0
**/

namespace App\Http\Controllers;

use App\Services\ModulesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class UpdateController extends Controller
{
    /**
     * @var ModulesService
     */
    protected $moduleService;

    public function __construct(
        ModulesService $module
    )
    {   
        $this->moduleService    =   $module;
    }
    public function updateDatabase()
    {
        return view( 'pages.database-update', [
            'title'     =>  __( 'Database Update' ),
            'redirect'  =>  session( 'after_update', ns()->route( 'ns.dashboard.home' ) ),
            'modules'   =>  collect( $this->moduleService->get() )->filter( fn( $module ) => count( $module[ 'migrations' ] ) > 0 )->toArray()
        ]);
    }

    public function runMigration( Request $request )
    {
        /**
         * Proceeding code migration.
         */
        if ( $request->input( 'file' ) ) {
            $file   =   ns()->update->getMatchingFullPath( 
                $request->input( 'file' ) 
            );
    
            Artisan::call( 'migrate --path=' . $file );
        }

        /**
         * proceeding the migration for
         * the provided module.
         */
        if ( $request->input( 'module' ) ) {
            $module     =   $request->input( 'module' );
            foreach( $module[ 'migrations' ] as $version => $files ) {
                foreach( $files as $file ) {
                    $this->moduleService->runMigration( $module[ 'namespace' ], $version, $file );
                }
            }
        }

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The migration has successfully run.' )
        ];
    }
}

