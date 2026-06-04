<?php

declare(strict_types=1);

namespace App\Mcp\Resources;

use App\Models\ProductCategory;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Resource;

class ProductCategoriesResource extends Resource
{
    protected string $uri = 'pos://resources/product-categories';

    protected string $mimeType = 'application/json';

    protected string $description = 'All product categories available in the POS system, which can be used when filtering products.';

    public function handle(): Response
    {
        try {
            $categories = ProductCategory::get();

            return Response::json($categories->toArray());
        } catch (\Throwable $e) {
            return Response::error($e->getMessage());
        }
    }
}
