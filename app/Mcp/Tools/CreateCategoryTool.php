<?php

namespace App\Mcp\Tools;

use App\Models\ProductCategory;
use Laravel\Mcp\Tools\Tool;
use Illuminate\Support\Facades\Request;

class CreateCategoryTool extends Tool
{
    public string $name = 'create_product_category';

    public string $description = 'Create a new product category in NexoPOS.';

    public function schema(): array
    {
        return [
            'name' => [
                'type' => 'string',
                'description' => 'The name of the new category.',
            ],
            'description' => [
                'type' => 'string',
                'description' => 'A description of the category.',
            ],
            'displays_on_pos' => [
                'type' => 'boolean',
                'description' => 'Whether this category should be displayed on the POS interface. Defaults to true.',
            ],
            'parent_id' => [
                'type' => 'number',
                'description' => 'ID of the parent category, if applicable.',
            ]
        ];
    }

    public function handle(array $parameters): array
    {
        if (empty($parameters['name'])) {
            return $this->error('The name parameter is required.');
        }

        $userId = auth()->id() ?? 1;

        $category = new ProductCategory();
        $category->name = $parameters['name'];
        if (isset($parameters['description'])) {
            $category->description = $parameters['description'];
        }
        if (isset($parameters['displays_on_pos'])) {
            $category->displays_on_pos = $parameters['displays_on_pos'];
        } else {
            $category->displays_on_pos = true;
        }
        if (isset($parameters['parent_id'])) {
            $category->parent_id = $parameters['parent_id'];
        }
        $category->author_id = $userId;
        
        $category->save();

        return [
            'id' => $category->id,
            'name' => $category->name,
            'message' => 'Product category created successfully.'
        ];
    }
}
