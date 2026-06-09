<?php

namespace App\Mcp\Tools;

use App\Models\Product;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class UpdateProductTool extends Tool
{
    public string $name = 'update_product';

    public string $description = 'Update an existing product main entry. Requires product id. Does not touch quantity or pricing, which are handled by product unit quantities.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->number()
                ->description('The ID of the product to update.')
                ->required(),
            'name' => $schema->string()
                ->description('The new name of the product.')
                ->nullable(),
            'description' => $schema->string()
                ->description('The new description.')
                ->nullable(),
            'status' => $schema->string()
                ->description('Product status (e.g. available, unavailable).')
                ->nullable(),
            'category_id' => $schema->number()
                ->description('ID of the product category.')
                ->nullable(),
            'barcode' => $schema->string()
                ->description('Product barcode.')
                ->nullable(),
            'sku' => $schema->string()
                ->description('Product SKU.')
                ->nullable(),
        ];
    }

    public function handle(\Laravel\Mcp\Request $request): \Laravel\Mcp\Response
    {
        if (empty($request->get('id'))) {
            return Response::error('The id parameter is required.');
        }

        $product = Product::find($request->get('id'));

        if (!$product) {
            return Response::error('Product not found.');
        }

        $fillable = ['name', 'description', 'status', 'category_id', 'barcode', 'sku']);

        foreach ($fillable as $field) {
            if ($request->get($field) !== null) {
                $product->$field = $request->get($field);
            }
        }
        
        $product->save();

        return Response::json([
            'id' => $product->id,
            'name' => $product->name,
            'message' => 'Product updated successfully.'
        ]);
    }
}
