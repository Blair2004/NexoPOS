<?php

/**
 * NexoPOS Controller
 *
 * @since  1.0
 **/

namespace App\Http\Controllers\Dashboard;

use App\Crud\RewardSystemCrud;
use App\Http\Controllers\DashboardController;
use App\Models\RewardSystem;

class RewardsSystemController extends DashboardController
{
    public function list()
    {
        return RewardSystemCrud::table();
    }

    public function create()
    {
        return RewardSystemCrud::form(
            config: [
                'view' => 'pages.dashboard.rewards-system.create',
            ]
        );
    }

    public function edit( RewardSystem $reward )
    {
        return RewardSystemCrud::form(
            entry: $reward,
            config: [
                'view' => 'pages.dashboard.rewards-system.create',
            ]
        );
    }
}
