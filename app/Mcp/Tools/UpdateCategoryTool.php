<?php

namespace App\Mcp\Tools;

use App\Models\ProductCategory;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class UpdateCategoryTool extends Tool
{
    public string $name = 'update_product_category';

    public string $description = 'Update an existing product category. Requires category id.';

    public function schema( JsonSchema $schema ): array
    {
        return [
            'name' => $schema->string()
                ->description( 'The new name of the category.' )
                ->nullable(),
            'description' => $schema->string()
                ->description( 'The new description of the category.' )
                ->nullable(),
            'displays_on_pos' => $schema->boolean()
                ->description( 'Whether this category should be displayed on the POS interface.' )
                ->nullable(),
            'parent_id' => $schema->number()
                ->description( 'ID of the parent category, if applicable. Can be null to remove parent.' )
                ->nullable(),
        ];
    }

    public function handle( Request $request ): Response
    {
        if ( empty( $request->get( 'id' ) ) ) {
            return Response::error( 'The id parameter is required.' );
        }

        $category = ProductCategory::find( $request->get( 'id' ) );

        if ( ! $category ) {
            return Response::error( 'Category not found.' );
        }

        if ( ( $request->get( 'name' ) !== null ) ) {
            $category->name = $request->get( 'name' );
        }
        if ( array_key_exists( 'description', $request->all() ) ) {
            $category->description = $request->get( 'description' );
        }
        if ( ( $request->get( 'displays_on_pos' ) !== null ) ) {
            $category->displays_on_pos = $request->get( 'displays_on_pos' );
        }
        if ( array_key_exists( 'parent_id', $request->all() ) ) {
            $category->parent_id = $request->get( 'parent_id' );
        }

        $category->save();

        return Response::json( [
            'id' => $category->id,
            'name' => $category->name,
            'message' => 'Product category updated successfully.',
        ] );
    }
}
