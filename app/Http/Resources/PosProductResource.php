<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;

class PosProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray( Request $request ): array
    {
        $product = Arr::only( parent::toArray( $request ), [
            'id', 'uuid', 'name', 'description', 'barcode', 'sku', 'product_type', 'type',
            'status', 'category_id', 'tax_group_id', 'tax_type', 'stock_management',
            'accurate_tracking', 'pinned', 'position', 'searchable', 'thumbnail_id',
            'unit_quantities', 'galleries', 'tax_group', 'selectedUnitQuantity',
        ] );

        $canEditPurchasePrice = $request->user() !== null
            && Gate::forUser( $request->user() )->allows( 'nexopos.pos.edit-purchase-price' );

        $product['unit_quantities'] = collect( $product['unit_quantities'] ?? [] )
            ->where( 'visible', true )
            ->map( function ( array $unitQuantity ) use ( $canEditPurchasePrice ): array {
                $visible = Arr::only( $unitQuantity, [
                    'id', 'product_id', 'unit_id', 'barcode', 'scale_plu', 'is_weighable',
                    'quantity', 'visible', 'sale_price', 'sale_price_edit', 'sale_price_net',
                    'sale_price_gross', 'sale_price_tax', 'wholesale_price', 'wholesale_price_edit',
                    'wholesale_price_net', 'wholesale_price_gross', 'wholesale_price_tax',
                    'custom_price', 'custom_price_edit', 'custom_price_net', 'custom_price_gross', 'unit',
                ] );

                if ( $canEditPurchasePrice ) {
                    $visible = array_merge( $visible, Arr::only( $unitQuantity, [ 'purchase_price', 'purchase_price_edit' ] ) );
                }

                return $visible;
            } )->values()->all();

        return $product;
    }
}
