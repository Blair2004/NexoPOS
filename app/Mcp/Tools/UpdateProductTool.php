<?php

namespace App\Mcp\Tools;

use App\Models\Product;
use Laravel\Mcp\Tools\Tool;

class UpdateProductTool extends Tool
{
    public string $name = 'update_product';

    public string $description = 'Update an existing product main entry. Requires product id. Does not touch quantity or pricing, which are handled by product unit quantities.';

    public function schema(): array
    {
        return [
            'id' => [
                'type' => 'number',
                'description' => 'The ID of the product to update.',
            ],
            'name' => [
                'type' => 'string',
                'description' => 'The new name of the product.',
            ],
            'description' => [
                'type' => 'string',
                'description' => 'The new description.',
            ],
            'status' => [
                'type' => 'string',
                'description' => 'Product status (e.g. available, unavailable).',
            ],
            'category_id' => [
                'type' => 'number',
                'description' => 'ID of the product category.',
            ],
            'barcode' => [
                'type' => 'string',
                'description' => 'Product barcode.',
            ],
            'sku' => [
                'type' => 'string',
                'description' => 'Product SKU.',
            ],
        ];
    }

    public function handle(array $parameters): array
    {
        if (empty($parameters['id'])) {
            return $this->error('The id parameter is required.');
        }

        $product = Product::find($parameters['id']);

        if (!$product) {
            return $this->error('Product not found.');
        }

        $fillable = ['name', 'description', 'status', 'category_id', 'barcode', 'sku'];

        foreach ($fillable as $field) {
            if (array_key_exists($field, $parameters)) {
                $product->$field = $parameters[$field];
            }
        }
        
        $product->save();

        return [
            'id' => $product->id,
            'name' => $product->name,
            'message' => 'Product updated successfully.'
        ];
    }
}
