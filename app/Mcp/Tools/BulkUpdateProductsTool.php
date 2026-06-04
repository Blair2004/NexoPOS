<?php

namespace App\Mcp\Tools;

use App\Models\Product;
use Illuminate\Contracts\JsonSchema\JsonSchema;
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

    public function handle(array $parameters): array
    {
        if (empty($parameters['ids']) || !is_array($parameters['ids'])) {
            return $this->error('The ids parameter must be a non-empty array of product IDs.');
        }

        $fillable = ['status', 'category_id'];
        $updateData = [];

        foreach ($fillable as $field) {
            if (array_key_exists($field, $parameters)) {
                $updateData[$field] = $parameters[$field];
            }
        }

        if (empty($updateData)) {
            return $this->error('No valid update fields provided. Please provide status or category_id.');
        }

        $updatedCount = Product::whereIn('id', $parameters['ids'])->update($updateData);

        return [
            'updated_count' => $updatedCount,
            'message' => "Successfully updated {$updatedCount} products."
        ];
    }
}
