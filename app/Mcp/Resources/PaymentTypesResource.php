<?php

declare(strict_types=1);

namespace App\Mcp\Resources;

use App\Models\PaymentType;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Resource;

class PaymentTypesResource extends Resource
{
    protected string $uri = 'pos://resources/payment-types';

    protected string $mimeType = 'application/json';

    protected string $description = 'All available payment types configured in the POS system (e.g. cash, card, mobile money).';

    public function handle(): Response
    {
        try {
            $types = PaymentType::all();

            return Response::json( $types->toArray() );
        } catch ( \Throwable $e ) {
            return Response::error( $e->getMessage() );
        }
    }
}
