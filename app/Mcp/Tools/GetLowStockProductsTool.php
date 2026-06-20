<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Services\ReportService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class GetLowStockProductsTool extends Tool
{
    protected string $name = 'get_low_stock_products';

    protected string $description = 'Retrieve products that have fallen below their configured low-stock alert threshold. Optionally filter by category or unit IDs.';

    public function schema( JsonSchema $schema ): array
    {
        return [
            'categories' => $schema->array()
                ->description( 'Optional array of category IDs to filter results. Leave empty for all categories.' )
                ->items( $schema->integer() )
                ->default( [] ),
            'units' => $schema->array()
                ->description( 'Optional array of unit IDs to filter results. Leave empty for all units.' )
                ->items( $schema->integer() )
                ->default( [] ),
        ];
    }

    public function handle( Request $request, ReportService $service ): Response
    {
        try {
            $categories = $request->get( 'categories', [] );
            $units = $request->get( 'units', [] );

            $results = $service->getLowStockProducts(
                is_array( $categories ) ? $categories : [],
                is_array( $units ) ? $units : []
            );

            return Response::json( $results->toArray() );
        } catch ( \Throwable $e ) {
            return Response::error( $e->getMessage() );
        }
    }
}
