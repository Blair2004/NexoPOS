<?php

declare(strict_types=1);

namespace App\Mcp\Resources;

use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Resource;

class StoreConfigResource extends Resource
{
    protected string $uri = 'pos://resources/store-config';

    protected string $mimeType = 'application/json';

    protected string $description = 'Current store configuration and settings, including name, currency, timezone, and contact information.';

    public function handle(): Response
    {
        try {
            $config = ns()->option->get( [
                'ns_store_name',
                'ns_currency_symbol',
                'ns_currency_precision',
                'ns_datetime_timezone',
                'ns_datetime_format',
                'ns_default_theme',
                'ns_store_address',
                'ns_store_phone',
                'ns_store_email',
            ] );

            return Response::json( (array) $config );
        } catch ( \Throwable $e ) {
            return Response::error( $e->getMessage() );
        }
    }
}
