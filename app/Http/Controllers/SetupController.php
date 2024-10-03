<?php

/**
 * NexoPOS Controller
 *
 * @since  1.0
 **/

namespace App\Http\Controllers;

use App\Classes\JsonResponse;
use App\Http\Requests\ApplicationConfigRequest;
use App\Services\SetupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SetupController extends Controller
{
    public function __construct( private SetupService $setup )
    {
        // ...
    }

    public function welcome( Request $request )
    {
        return view( 'pages.setup.welcome', [
            'title' => __( 'Welcome &mdash; NexoPOS' ),
            'languages' => config( 'nexopos.languages' ),
            'lang' => $request->query( 'lang' ) ?: 'en',
        ] );
    }

    public function checkDatabase( Request $request )
    {
        return $this->setup->saveDatabaseSettings( $request );
    }

    public function checkDbConfigDefined( Request $request )
    {
        return $this->setup->testDBConnexion();
    }

    public function saveConfiguration( ApplicationConfigRequest $request )
    {
        return $this->setup->runMigration( $request->all() );
    }

    public function checkExistingCredentials()
    {
        try {
            if ( DB::connection()->getPdo() ) {
                /**
                 * We believe from here the app should update the .env file to ensure
                 * the APP_URL and others values are updated with the actual domain name.
                 */
                $this->setup->updateAppURL();

                return JsonResponse::success( [
                    'message' => __( 'The database connection has been successfully established.' ),
                ] );
            }
        } catch ( \Exception $e ) {
            return JsonResponse::error( [
                'message' => $e->getMessage(),
            ] );
        }
    }
}
