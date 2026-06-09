<?php

namespace App\Mcp\Tools;

use App\Models\Product;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class BulkUpdateProductsTool extends Tool
{
    public string $name = 'bulk_update_products';

    public string $description = 'Applies the provided field updates to an array of product IDs.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'ids' => $schema->array()
                ->items($schema->number())
                ->description('List of Product IDs to update.')
                ->required(),
            'status' => $schema->string()
                ->description('The status to set on all provided products (e.g. available, unavailable)')
                ->nullable(),
            'category_id' => $schema->number()
                ->description('Move all provided products to this category ID.')
                ->nullable(),
        ];
    }

    public function handle(\Laravel\Mcp\Request $request): \Laravel\Mcp\Response
    {
        if (empty($request->get('ids')) || !is_array($request->get('ids'))) {
            return Response::error('The ids parameter must be a non-empty array of product IDs.');
        }

        $fillable = ['status', 'category_id']);
        $updateData = []);

        foreach ($fillable as $field) {
            if ($request->get($field) !== null) {
                $updateData[$field] = $request->get($field);
            }
        }

        if (empty($updateData)) {
            return Response::error('No valid update fields provided. Please provide status or category_id.');
        }

        $updatedCount = Product::whereIn('id', $request->get('ids'))->update($updateData);

        return Response::json([
            'updated_count' => $updatedCount,
            'message' => "Successfully updated {$updatedCount} products."
        ]);
    }
}
