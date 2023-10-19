<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\DashboardController;
use App\Services\DemoService;
use App\Services\ResetService;
use Database\Seeders\DefaultSeeder;
use Database\Seeders\FirstDemoSeeder;
use Exception;
use Illuminate\Http\Request;

class ResetController extends DashboardController
{
    /**
     * @var ResetService
     */
    protected $resetService;

    /**
     * @param DemoService
     */
    protected $demoService;

    public function __construct(
        ResetService $resetService,
        DemoService $demoService
    ) {
        $this->resetService = $resetService;
        $this->demoService = $demoService;
    }

    /**
     * perform a hard reset
     *
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
     *
     * @return array
     */
    public function truncateWithDemo( Request $request )
    {
        $this->resetService->softReset( $request );

        switch ( $request->input( 'mode' ) ) {
            case 'wipe_plus_grocery':
                $this->demoService->run( $request->all() );
                break;
            case 'wipe_plus_simple':
                ( new FirstDemoSeeder )->run();
                break;
            case 'default':
                ( new DefaultSeeder )->run();
                break;
            default:
                $this->resetService->handleCustom(
                    $request->all()
                );
            break;
        }

        return [
            'status' => 'success',
            'message' => __( 'The database has been successfully seeded.' ),
        ];
    }
}
