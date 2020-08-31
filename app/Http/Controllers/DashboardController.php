<?php

/**
 * NexoPOS Controller
 * @since  1.0
**/

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MenuService;
use App\Models\ProductCategory;


use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\View;

class DashboardController extends Controller
{
    protected $menuService;

    public function __construct()
    {
        $this->menuService    =   app()->make( MenuService::class );
    }
    
    public function home()
    {
        return view( 'pages.dashboard.home', [
            'menus' =>  $this->menuService,
            'title' =>  __( 'Dashboard' )
        ]);
    }

    protected function view( $path, $data = [])
    {
        return view( $path, array_merge([
            'menus'     =>   $this->menuService
        ], $data ));
    }
}

