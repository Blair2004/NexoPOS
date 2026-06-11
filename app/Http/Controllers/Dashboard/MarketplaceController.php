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
            'categories' => $request->query( 'categories', null ),
            'search' => $request->query( 'search', null ),
        ]);
    }

    public function oauthAuthorize()
    {
        $url = $this->marketplaceService->authorize();

        return $url;
    }

    public function oauthCallback( Request $request )
    {
        $validated = $request->validate( [
            'code' => 'required|string',
            'state' => 'required|string',
        ] );

        return $this->marketplaceService->handleCallback( $validated );
    }

    public function addToCart( Request $request )
    {
        $validated = $request->validate( [
            'item_id' => 'required|integer',
        ] );

        return $this->marketplaceService->addToCart( $validated[ 'item_id' ] );
    }

    public function getLicenses( int | string $itemId )
    {
        return $this->marketplaceService->getLicenses( $itemId );
    }

    public function downloadModule( Request $request )
    {
        $validated = $request->validate( [
            'item_id' => 'required|integer',
            'license_id' => 'required|string',
        ] );

        return $this->marketplaceService->downloadModule( $validated[ 'item_id' ], $validated[ 'license_id' ] );
    }

    public function getCategories()
    {
        return $this->marketplaceService->getCategories();
    }
}
