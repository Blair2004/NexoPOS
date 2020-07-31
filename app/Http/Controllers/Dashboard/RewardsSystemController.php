<?php

/**
 * NexoPOS Controller
 * @since  1.0
**/

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\View;

class RewardsSystemController extends DashboardController
{
    public function list()
    {
        return $this->view( 'pages.dashboard.crud.table', [
            'title'         =>  __( 'Rewards System' ),
            'description'   =>  __( 'Manage all rewards program.' ),
            'srcUrl'        =>  url( '/api/nexopos/v4/crud/ns.rewards-system' ),
            'createLink'    =>  url( '/dashboard/customers/rewards-system/create' )
        ]);
    }

    public function create()
    {
        return $this->view( 'pages.dashboard.rewards-system.create', [
            'title'         =>  __( 'Create A Reward System' ),
            'description'   =>  __( 'Add a new reward system.' ),
            'srcUrl'        =>  url( '/api/nexopos/v4/crud/ns.rewards-system/form-config' ),
            'submitUrl'     =>  url( '/api/nexopos/v4/crud/ns.rewards-system' ),
            'returnLink'    =>  url( '/dashboard/customers/reward-systems' )
        ]);
    }

    public function edit()
    {

    }
}

