<?php

use App\Mcp\Servers\POSServer;
use App\Services\ModulesService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Laravel\Mcp\Facades\Mcp;
use Modules\NsOxen\Http\Middleware\EnsureOxenConnection;
use Modules\NsOxen\Mcp\Servers\OxenServer;

Mcp::web( '/mcp/pos', POSServer::class )
    ->middleware( ['auth:sanctum', 'throttle:mcp'] );

if ( app( ModulesService::class )->getEnabledAndAutoloadedModules()->contains( fn ( array $module ): bool => ( $module[ 'namespace' ] ?? null ) === 'NsOxen' ) ) {
    Mcp::web( '/mcp/oxen', OxenServer::class )
        ->middleware( [ 'auth:sanctum', EnsureOxenConnection::class, 'throttle:oxen-mcp' ] );
}

Route::get( '/mcp/reports/{filename}', function ( string $filename ) {
    abort_unless( preg_match( '/^[A-Za-z0-9._-]+$/', $filename ) === 1, 404 );

    $path = 'mcp-reports/' . $filename;
    abort_unless( Storage::disk( 'ns-temp' )->exists( $path ), 404 );

    $extension = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
    $contentTypes = [
        'html' => 'text/html; charset=UTF-8',
        'pdf' => 'application/pdf',
        'csv' => 'text/csv; charset=UTF-8',
    ];
    abort_unless( isset( $contentTypes[$extension] ), 404 );

    return response( Storage::disk( 'ns-temp' )->get( $path ), 200, [
        'Content-Type' => $contentTypes[$extension],
        'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        'X-Robots-Tag' => 'noindex, nofollow',
    ] );
} )->middleware( 'signed' )->name( 'mcp.reports.download' );
