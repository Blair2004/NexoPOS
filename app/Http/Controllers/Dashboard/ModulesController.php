<?php

/**
 * NexoPOS Controller
 * @since  1.0
**/

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\DashboardController;
use App\Http\Requests\ModuleUploadRequest;
use App\Models\ProductCategory;
use App\Services\ModulesService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Validator;

class ModulesController extends DashboardController
{
    /**
     * @var ModulesService
     */
    protected $modules;

    public function __construct(
        ModulesService $modules
    )
    {
        parent::__construct();

        $this->modules  =   $modules;
    }

    public function listModules( $page = '' )
    {
        return $this->view( 'pages.dashboard.modules.list', [
            'title'         =>      __( 'Modules List' ),
            'description'   =>  __( 'List all available modules.' ),
        ]);
    }

    public function getModules( $argument = '' )
    {        
        switch( $argument ) {
            case '':
                $list   =   $this->modules->get();
            break;
            case 'enabled':
                $list   =   $this->modules->getEnabled();
            break;
            case 'disabled':
                $list   =   $this->modules->getDisabled();
            break;
        };

        return [
            'modules'           =>  $list,
            'total_enabled'     =>  count( $this->modules->getEnabled() ),
            'total_disabled'    =>  count( $this->modules->getDisabled() )
        ];
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
        return $this->view( 'pages.dashboard.modules.upload', [
            'title'     =>      __( 'Upload A Module' ),
            'description'   =>  __( 'Extends NexoPOS features with some new modules.' )
        ]);
    }

    public function uploadModule( ModuleUploadRequest $request )
    {
        $result     =   $this->modules->upload( $request->file( 'module' ) );

        if ( isset( $result[ 'action' ] ) ) {

        }

        /**
         * if the module upload was successful
         */
        if ( $result[ 'status' ] === 'success' ) {
            return redirect( route( 'ns.dashboard.modules.list' ) )->with( $result );
        } else {
            $validator      =   Validator::make( $request->all(), [] );
            $validator->errors()->add( 'module', $result[ 'message' ] );
            return redirect( route( 'ns.dashboard.modules.upload' ) )->withErrors( $validator );
        }
    }
}

