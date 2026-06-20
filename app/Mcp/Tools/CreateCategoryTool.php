<?php

namespace App\Mcp\Tools;

use App\Models\ProductCategory;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Request;

class CreateCategoryTool extends Tool
{
    public string $name = 'create_product_category';

    public string $description = 'Create a new product category in NexoPOS.';

    public function schema( JsonSchema $schema ): array
    {
        return [
            'name' => $schema->string()
                ->description('The name of the new category.')
                ->required(),
            'description' => $schema->string()
                ->description('A description of the category.')
                ->nullable(),
            'displays_on_pos' => $schema->boolean()
                ->description('Whether this category should be displayed on the POS interface. Defaults to true.')
                ->nullable(),
            'parent_id' => $schema->number()
                ->description('ID of the parent category, if applicable.')
                ->nullable(),
        ];
    }

    public function handle(\Laravel\Mcp\Request $request): \Laravel\Mcp\Response
    {
        if (empty($request->get('name'))) {
            return Response::error('The name parameter is required.');
        }

        $userId = auth()->id() ?? 1;

        $category = new ProductCategory();
        $category->name = $request->get('name');
        if (($request->get('description') !== null)) {
            $category->description = $request->get('description');
        }
        if (($request->get('displays_on_pos') !== null)) {
            $category->displays_on_pos = $request->get('displays_on_pos');
        } else {
            $category->displays_on_pos = true;
        }
        if (($request->get('parent_id') !== null)) {
            $category->parent_id = $request->get('parent_id');
        }
        $category->author_id = $userId;
        
        $category->save();

        return Response::json([
            'id' => $category->id,
            'name' => $category->name,
            'message' => 'Product category created successfully.'
        ]);
    }
}
