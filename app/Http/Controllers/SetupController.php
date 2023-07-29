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
    public function welcome( Request $request )
    {
        return view( 'pages.setup.welcome', [
            'title'     => __( 'Welcome &mdash; NexoPOS' ),
            'languages' =>  config( 'nexopos.languages' ),
            'lang' => $request->query( 'lang' ) ?: 'en',
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
