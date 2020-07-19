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

use Illuminate\Support\Facades\View;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->menus    =   new MenuService;
    }
    
    public function home()
    {
        return view( 'pages.dashboard.home', [
            'menus' =>  $this->menus
        ]);
    }

    protected function view( $path, $data = [])
    {
        return view( $path, array_merge([
            'menus'     =>   $this->menus
        ], $data ));
    }
}

