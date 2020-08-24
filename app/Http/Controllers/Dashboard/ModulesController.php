<?php

/**
 * NexoPOS Controller
 * @since  1.0
**/

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;


use Tendoo\Core\Exceptions\CoreException;

use App\Services\Modules;
use App\Models\ProductCategory;
use Exception;

class ModulesController extends DashboardController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function listModules()
    {
        /**
         * @var Modules $modules;
         */
        $modules        =   app()->make( Modules::class );

        return $this->view( 'pages.dashboard.modules.list', [
            'title'     =>      __( 'Modules List' ),
            'description'   =>  __( 'List all available modules.' ),
            'modules'       =>  $modules->get()
        ]);
    }

    public function uploadModule()
    {
        return $this->view( 'pages.dashboard.modules.upload', [
            'title'     =>      __( 'Upload A Module' ),
            'description'   =>  __( 'Extends NexoPOS features with some new modules.' )
        ]);
    }
}

