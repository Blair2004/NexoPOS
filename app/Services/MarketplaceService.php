<?php
namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MarketplaceService
{
    public function getModules( $data = [] )
    {
        $queryParams =  [
            'per_page' => $data[ 'per_page' ] ?? 12,
            'page' => $data[ 'page' ] ?? 1,
            'type' => 'zip'
        ];

        $request = Http::withHeader( 'X-NEXOPOS-DOMAIN', $data[ 'host' ] ?? request()->getHost() );

        if ( env( 'APP_ENV' ) === 'local' ) {
            $request->withoutVerifying();
        }

        return $request->accept( 'application/json' )
            ->withoutVerifying()
            ->get( env( 'MARKETPLACE_DOMAIN', 'https://my.nexopos.com' ) . '/api/nexoplatform/marketplace/items?' . http_build_query( $queryParams ) );
    }
}