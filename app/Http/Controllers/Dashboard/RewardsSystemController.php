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
use Illuminate\Support\Facades\View;

class RewardsSystemController extends DashboardController
{
    public function list()
    {
        return RewardSystemCrud::table();
    }

    public function create()
    {
        return RewardSystemCrud::form();
    }

    public function edit( RewardSystem $reward )
    {
        return RewardSystemCrud::form( $reward );
    }
}
