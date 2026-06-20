<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Services\ProductService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class SearchProductsTool extends Tool
{
    protected string $name = 'search_products';

    protected string $description = 'Search for products by name or keyword. Returns a list of matching products with pricing, stock, and category information.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()
                ->description('The search keyword to filter products by name.')
                ->nullable(),
            'limit' => $schema->integer()
                ->description('Maximum number of results to return.')
                ->default(5)
                ->min(1)
                ->max(50),
        ];
    }

    public function handle(Request $request, ProductService $service): Response
    {
        try {
            $results = $service->searchProduct(
                $request->get('search'),
                (int) $request->get('limit', 5)
            );

            return Response::json($results->toArray());
        } catch (\Throwable $e) {
            return Response::error($e->getMessage());
        }
    }
}
