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
class SearchCustomersTool extends Tool
{
    protected string $name = 'search_customers';

    protected string $description = 'Search for customers by name, email, username, or phone number. Returns up to 10 matching customers.';

    public function schema( JsonSchema $schema ): array
    {
        return [
            'query' => $schema->string()
                ->description( 'The search term to match against customer first name, last name, username, email, or phone.' )
                ->required(),
        ];
    }

    public function handle( Request $request, CustomerService $service ): Response
    {
        try {
            $results = $service->search( $request->get( 'query', '' ) );

            return Response::json( $results->toArray() );
        } catch ( \Throwable $e ) {
            return Response::error( $e->getMessage() );
        }
    }
}
