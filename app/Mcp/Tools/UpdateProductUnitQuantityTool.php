<?php

namespace App\Mcp\Tools;

use App\Models\ProductUnitQuantity;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class UpdateProductUnitQuantityTool extends Tool
{
    public string $name = 'update_product_unit_quantity';

    public string $description = 'Updates product pricing, inventory tracking, barcodes, etc. for a specific unit quantity. Use get_product_unit_quantities to find the correct ID.';

    public function schema( JsonSchema $schema ): array
    {
        return [
            'sale_price' => $schema->number()
                ->description( 'The sale price of the unit.' )
                ->nullable(),
            'wholesale_price' => $schema->number()
                ->description( 'The wholesale price.' )
                ->nullable(),
            'custom_price' => $schema->number()
                ->description( 'A custom price.' )
                ->nullable(),
            'quantity' => $schema->number()
                ->description( 'The current stock quantity.' )
                ->nullable(),
            'barcode' => $schema->string()
                ->description( 'The barcode for this unit.' )
                ->nullable(),
            'scale_plu' => $schema->string()
                ->description( 'The scale PLU if weighable.' )
                ->nullable(),
            'is_weighable' => $schema->boolean()
                ->description( 'Whether this unit is weighable.' )
                ->nullable(),
        ];
    }

    public function handle( Request $request ): Response
    {
        if ( empty( $request->get( 'id' ) ) ) {
            return Response::error( 'The id parameter is required.' );
        }

        $unitQuantity = ProductUnitQuantity::find( $request->get( 'id' ) );

        if ( ! $unitQuantity ) {
            return Response::error( 'Product Unit Quantity not found.' );
        }

        $fillable = [
            'sale_price',
            'wholesale_price',
            'custom_price',
            'quantity',
            'barcode',
            'scale_plu',
            'is_weighable',
        ];

        foreach ( $fillable as $field ) {
            if ( $request->get( $field ) !== null ) {
                $unitQuantity->$field = $request->get( $field );
            }
        }

        $unitQuantity->save();

        return Response::json( [
            'id' => $unitQuantity->id,
            'product_id' => $unitQuantity->product_id,
            'message' => 'Product Unit Quantity updated successfully.',
        ] );
    }
}
