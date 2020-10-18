<?php

/**
 * NexoPOS Controller
 * @since  1.0
**/

namespace App\Http\Controllers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

use App\Services\Options;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UpdateController extends Controller
{
    public function updateDatabase()
    {
        return view( 'pages.database-update', [
            'title'     =>  __( 'Database Update' ),
            'redirect'  =>  session( 'after_update', route( 'ns.dashboard.home' ) )
        ]);
    }

    public function runMigration( Request $request )
    {
        $file   =   ns()->update->getMatchingFullPath( 
            $request->input( 'file' ) 
        );

        Artisan::call( 'migrate --path=' . $file );

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The migration has successfully run.' )
        ];
    }
}

