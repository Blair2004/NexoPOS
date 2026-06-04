<?php

use Laravel\Mcp\Facades\Mcp;
use App\Mcp\Servers\POSServer;

Mcp::web( '/mcp/pos', POSServer::class )
        ->middleware(['auth:sanctum', 'throttle:mcp']);