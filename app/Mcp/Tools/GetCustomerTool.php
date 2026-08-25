<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Services\CustomerService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class GetCustomerTool extends Tool
{
    protected string $name = 'get_customer';

    protected string $description = 'Retrieve a single customer by their numeric ID, including their billing and shipping addresses.';

    public function schema( JsonSchema $schema ): array
    {
        return [
            'id' => $schema->integer()
                ->description( 'The customer\'s numeric ID.' )
                ->required(),
        ];
    }

    public function handle( Request $request, CustomerService $service ): Response
    {
        try {
            $customer = $service->get( (int) $request->get( 'id' ) );

            return Response::json( $customer->toArray() );
        } catch ( \Throwable $e ) {
            return Response::error( $e->getMessage() );
        }
    }
}
