<?php

/**
 * NexoPOS Controller
 * @since  1.0
**/

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\DashboardController;
use App\Models\RewardSystem;
use Illuminate\Support\Facades\View;

class RewardsSystemController extends DashboardController
{
    public function list()
    {
        return $this->view( 'pages.dashboard.crud.table', [
            'title'         =>  __( 'Rewards System' ),
            'description'   =>  __( 'Manage all rewards program.' ),
            'src'           =>  ns()->url( '/api/nexopos/v4/crud/ns.rewards-system' ),
            'createUrl'     =>  ns()->url( '/dashboard/customers/rewards-system/create' )
        ]);
    }

    public function create()
    {
        return $this->view( 'pages.dashboard.rewards-system.create', [
            'title'         =>  __( 'Create A Reward System' ),
            'description'   =>  __( 'Add a new reward system.' ),
            'src'           =>  ns()->url( '/api/nexopos/v4/crud/ns.rewards-system/form-config' ),
            'submitUrl'     =>  ns()->url( '/api/nexopos/v4/crud/ns.rewards-system' ),
            'returnUrl'     =>  ns()->url( '/dashboard/customers/rewards-system' )
        ]);
    }

    public function edit( RewardSystem $reward )
    {
        return $this->view( 'pages.dashboard.rewards-system.create', [
            'title'         =>  __( 'Edit A Reward System' ),
            'description'   =>  __( 'edit an existing reward system with the rules attached.' ),
            'src'           =>  ns()->url( '/api/nexopos/v4/crud/ns.rewards-system/form-config/' . $reward->id ),
            'submitMethod'  =>  'PUT',
            'submitUrl'     =>  ns()->url( '/api/nexopos/v4/crud/ns.rewards-system/' . $reward->id ),
            'returnUrl'     =>  ns()->url( '/dashboard/customers/rewards-system' ),
            'reward'        =>  $reward
        ]);
    }
}

