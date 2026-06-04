<?php

namespace App\Mcp\Tools;

use App\Models\ProductUnitQuantity;
use Laravel\Mcp\Tools\Tool;

class UpdateProductUnitQuantityTool extends Tool
{
    public string $name = 'update_product_unit_quantity';

    public string $description = 'Updates product pricing, inventory tracking, barcodes, etc. for a specific unit quantity. Use get_product_unit_quantities to find the correct ID.';

    public function schema(): array
    {
        return [
            'id' => [
                'type' => 'number',
                'description' => 'The ID of the ProductUnitQuantity record to update.',
            ],
            'sale_price' => [
                'type' => 'number',
                'description' => 'The sale price of the unit.',
            ],
            'wholesale_price' => [
                'type' => 'number',
                'description' => 'The wholesale price.',
            ],
            'custom_price' => [
                'type' => 'number',
                'description' => 'A custom price.',
            ],
            'quantity' => [
                'type' => 'number',
                'description' => 'The current stock quantity.',
            ],
            'barcode' => [
                'type' => 'string',
                'description' => 'The barcode for this unit.',
            ],
            'scale_plu' => [
                'type' => 'string',
                'description' => 'The scale PLU if weighable.',
            ],
            'is_weighable' => [
                'type' => 'boolean',
                'description' => 'Whether this unit is weighable.',
            ],
        ];
    }

    public function handle(array $parameters): array
    {
        if (empty($parameters['id'])) {
            return $this->error('The id parameter is required.');
        }

        $unitQuantity = ProductUnitQuantity::find($parameters['id']);

        if (!$unitQuantity) {
            return $this->error('Product Unit Quantity not found.');
        }

        $fillable = [
            'sale_price', 
            'wholesale_price', 
            'custom_price', 
            'quantity', 
            'barcode', 
            'scale_plu', 
            'is_weighable'
        ];

        foreach ($fillable as $field) {
            if (array_key_exists($field, $parameters)) {
                $unitQuantity->$field = $parameters[$field];
            }
        }
        
        $unitQuantity->save();

        return [
            'id' => $unitQuantity->id,
            'product_id' => $unitQuantity->product_id,
            'message' => 'Product Unit Quantity updated successfully.'
        ];
    }
}
