<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\DashboardController;
use App\Services\MarketplaceService;
use Illuminate\Http\Request;

class MarketplaceController extends DashboardController
{
    public function __construct( protected MarketplaceService $marketplaceService )
    {
        // ...
    }

    public function getModules( Request $request )
    {
        return $this->marketplaceService->getModules([
            'host' => $request->getHost(),
            'per_page' => $request->query( 'per_page', 12 ),
            'page' => $request->query( 'page', 1 ),
        ]);
    }
}
