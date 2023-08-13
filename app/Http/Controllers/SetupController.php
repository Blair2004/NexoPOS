<?php

/**
 * NexoPOS Controller
 *
 * @since  1.0
 **/

namespace App\Http\Controllers;

use App\Http\Requests\ApplicationConfigRequest;
use App\Services\SetupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        $setup = new SetupService;

        return $setup->saveDatabaseSettings( $request );
    }

    public function checkDbConfigDefined( Request $request )
    {
        $setup = new SetupService;

        return $setup->testDBConnexion();
    }

    public function saveConfiguration( ApplicationConfigRequest $request )
    {
        $setup = new SetupService;

        return $setup->runMigration( $request->all() );
    }

    public function checkExistingCredentials()
    {
        try {
            if ( DB::connection()->getPdo() ) {
                return [
                    'status'    =>  'success'
                ];
            }
        } catch (\Exception $e) {
            return response()->json([
                'status'    =>  'failed'
            ], 403 );
        }
    }
}
