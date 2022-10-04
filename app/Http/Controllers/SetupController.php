<?php

/**
 * NexoPOS Controller
 *
 * @since  1.0
 **/

namespace App\Http\Controllers;

use App\Http\Requests\ApplicationConfigRequest;
use App\Services\Setup;
use Illuminate\Http\Request;

class SetupController extends Controller
{
    public function welcome()
    {
        return view( 'pages.setup.welcome', [
            'title' => __( 'NexoPOS 4 &mdash; Setup Wizard' ),
        ]);
    }

    public function checkDatabase( Request $request )
    {
        $setup = new Setup;

        return $setup->saveDatabaseSettings( $request );
    }

    public function checkDbConfigDefined( Request $request )
    {
        $setup = new Setup;

        return $setup->testDBConnexion();
    }

    public function saveConfiguration( ApplicationConfigRequest $request )
    {
        $setup = new Setup;

        return $setup->runMigration( $request->all() );
    }
}
