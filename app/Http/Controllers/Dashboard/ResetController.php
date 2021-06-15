<?php
namespace App\Http\Controllers\Dashboard;

use App\Classes\Hook;
use App\Http\Controllers\DashboardController;
use App\Services\ResetService;
use Database\Seeders\DefaultSeeder;
use Database\Seeders\FirstDemoSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Exception;


class ResetController extends DashboardController
{
    /**
     * @var ResetService $resetService
     */
    protected $resetService;

    public function __construct(
        ResetService $resetService
    )
    {
        $this->resetService     =   $resetService;
    }

    /**
     * perform a hard reset
     * @param Request $request
     * @return array $array
     */
    public function hardReset( Request $request )
    {
        if ( $request->input( 'authorization' ) !== env( 'NS_AUTHORIZATION' ) ) {
            throw new Exception( __( 'Invalid authorization code provided.' ) );
        }

        return $this->resetService->hardReset();
    }

    /**
     * Will truncate the database and seed
     * @param Request $request
     * @return array
     */
    public function truncateWithDemo( Request $request )
    {
        $this->resetService->softReset( $request );

        switch( $request->input( 'mode' ) ) {
            case 'wipe_plus_grocery':
                ( new FirstDemoSeeder )->run();
            break;
            case 'wipe_plus_simple':
                ( new FirstDemoSeeder )->run();
            break;
            default:
                ( new DefaultSeeder )->run();
            break;
        }

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The database has been successfully seeded.' )
        ];
    }
}