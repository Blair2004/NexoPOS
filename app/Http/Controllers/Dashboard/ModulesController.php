<?php

/**
 * NexoPOS Controller
 *
 * @since  1.0
 **/

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\DashboardController;
use App\Http\Requests\ModuleUploadRequest;
use App\Services\DateService;
use App\Services\ModulesService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

class ModulesController extends DashboardController
{
    public function __construct(
        protected ModulesService $modules,
        protected DateService $dateService
    ) {
        $this->middleware( function ( $request, $next ) {
            ns()->restrict( [ 'manage.modules' ] );

            return $next( $request );
        } );
    }

    public function listModules( $page = '' )
    {
        return View::make( 'pages.dashboard.modules.list', [
            'title' => __( 'Modules List' ),
            'description' => __( 'List all available modules.' ),
        ] );
    }

    public function downloadModule( $identifier )
    {
        ns()->restrict( [ 'manage.modules' ] );

        $module = $this->modules->get( $identifier );
        $download = $this->modules->extract( $identifier );
        $relativePath = substr( $download[ 'path' ], strlen( base_path() ) );

        return Storage::disk( 'ns' )->download( $relativePath, Str::slug( $module[ 'name' ] ) . '-' . $module[ 'version' ] . '.zip' );
    }

    /**
     * Get modules using various statuses
     *
     * @param string status
     * @return array of modules
     */
    public function getModules( $argument = '' )
    {
        switch ( $argument ) {
            case '':
                $list = $this->modules->get();
                break;
            case 'enabled':
                $list = $this->modules->getEnabled();
                break;
            case 'disabled':
                $list = $this->modules->getDisabled();
                break;
        }

        return [
            'modules' => $list,
            'total_enabled' => count( $this->modules->getEnabled() ),
            'total_disabled' => count( $this->modules->getDisabled() ),
        ];
    }

    /**
     * Performs a single migration file for a specific module
     *
     * @param string module namespace
     * @return Request $request
     * @return array   response
     *
     * @deprecated
     */
    public function migrate( $namespace, Request $request )
    {
        $module = $this->modules->get( $namespace );
        $result = $this->modules->runMigration( $module[ 'namespace' ], $request->input( 'version' ), $request->input( 'file' ) );

        if ( $result[ 'status' ] === 'error' ) {
            throw new Exception( $result[ 'message' ] );
        }

        return $result;
    }

    /**
     * @param string module identifier
     * @return array operation response
     */
    public function disableModule( $argument )
    {
        return $this->modules->disable( $argument );
    }

    /**
     * @param string module identifier
     * @return array operation response
     */
    public function enableModule( $argument )
    {
        return $this->modules->enable( $argument );
    }

    /**
     * @param string module identifier
     * @return array operation response
     */
    public function deleteModule( $argument )
    {
        return $this->modules->delete( $argument );
    }

    public function showUploadModule()
    {
        return View::make( 'pages.dashboard.modules.upload', [
            'title' => __( 'Upload A Module' ),
            'description' => __( 'Extends NexoPOS features with some new modules.' ),
        ] );
    }

    /**
     * Upload a module. Except a "module" provided as a file input
     *
     * @return Json|Redirect response
     */
    public function uploadModule( ModuleUploadRequest $request )
    {
        $result = $this->modules->upload( $request->file( 'module' ) );

        /**
         * if the module upload was successful
         */
        if ( $result[ 'status' ] === 'success' ) {
            return redirect( ns()->route( 'ns.dashboard.modules-list' ) )->with( $result );
        } else {
            $validator = Validator::make( $request->all(), [] );
            $validator->errors()->add( 'module', $result[ 'message' ] );

            return redirect( ns()->route( 'ns.dashboard.modules-upload' ) )->withErrors( $validator );
        }
    }
}
