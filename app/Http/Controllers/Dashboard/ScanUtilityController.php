<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\DashboardController;
use Illuminate\Http\Request;

class ScanUtilityController extends DashboardController
{
    public function getBarcodeReaderState( Request $request )
    {
        $access_token = ns()->option->get( 'pos.wireless-barcode.access_token' );
        $refresh_token = ns()->option->get( 'pos.wireless-barcode.refresh_token' );
    }
}
