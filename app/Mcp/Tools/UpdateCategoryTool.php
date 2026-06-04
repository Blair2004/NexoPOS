<?php

namespace App\Mcp\Tools;

use App\Models\ProductCategory;
use Laravel\Mcp\Tools\Tool;

class UpdateCategoryTool extends Tool
{
    public string $name = 'update_product_category';

    public string $description = 'Update an existing product category. Requires category id.';

    public function schema(): array
    {
        return [
            'id' => [
                'type' => 'number',
                'description' => 'The ID of the category to update.',
            ],
            'name' => [
                'type' => 'string',
                'description' => 'The new name of the category.',
            ],
            'description' => [
                'type' => 'string',
                'description' => 'The new description of the category.',
            ],
            'displays_on_pos' => [
                'type' => 'boolean',
                'description' => 'Whether this category should be displayed on the POS interface.',
            ],
            'parent_id' => [
                'type' => 'number',
                'description' => 'ID of the parent category, if applicable. Can be null to remove parent.',
            ]
        ];
    }

    public function handle(array $parameters): array
    {
        if (empty($parameters['id'])) {
            return $this->error('The id parameter is required.');
        }

        $category = ProductCategory::find($parameters['id']);

        if (!$category) {
            return $this->error('Category not found.');
        }

        if (isset($parameters['name'])) {
            $category->name = $parameters['name'];
        }
        if (array_key_exists('description', $parameters)) {
            $category->description = $parameters['description'];
        }
        if (isset($parameters['displays_on_pos'])) {
            $category->displays_on_pos = $parameters['displays_on_pos'];
        }
        if (array_key_exists('parent_id', $parameters)) {
            $category->parent_id = $parameters['parent_id'];
        }
        
        $category->save();

        return [
            'id' => $category->id,
            'name' => $category->name,
            'message' => 'Product category updated successfully.'
        ];
    }
}
