<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\DashboardController;
use App\Services\DateService;
use App\Services\DemoService;
use App\Services\ResetService;
use App\Services\SetupService;
use Illuminate\Http\Request;

class ResetController extends DashboardController
{
    public function __construct(
        protected ResetService $resetService,
        protected DemoService $demoService,
        protected DateService $dateService,
        protected SetupService $setupService
    ) {
        // ...
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
