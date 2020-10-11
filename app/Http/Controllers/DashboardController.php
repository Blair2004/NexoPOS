<?php

/**
 * NexoPOS Controller
 * @since  1.0
**/

namespace App\Http\Controllers;

use App\Models\DashboardDay;
use Illuminate\Http\Request;
use App\Services\MenuService;
use App\Models\ProductCategory;
use App\Services\DateService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\View;

class DashboardController extends Controller
{
    protected $menuService;
    protected $dateService;

    public function __construct()
    {
        $this->dateService      =   app()->make( DateService::class );
        $this->menuService      =   app()->make( MenuService::class );
    }
    
    public function home()
    {
        return view( 'pages.dashboard.home', [
            'menus' =>  $this->menuService,
            'title' =>  __( 'Dashboard' )
        ]);
    }

    public function experiments()
    {
        return view( 'pages.dashboard.experiments', [
            'menus' =>  $this->menuService,
            'title' =>  __( 'Experiments' )
        ]);
    }

    protected function view( $path, $data = [])
    {
        return view( $path, array_merge([
            'menus'     =>   $this->menuService
        ], $data ));
    }

    public function getCards()
    {
        $todayStarts    =   $this->dateService->copy()->startOfDay()->toDateTimeString();
        $todayEnds      =   $this->dateService->copy()->endOfDay()->toDateTimeString();
        return DashboardDay::from( $todayStarts )
            ->to( $todayEnds )
            ->first();
    }
}

