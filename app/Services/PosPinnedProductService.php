<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PosPinnedProductService
{
    public function get(): Collection
    {
        return Product::query()
            ->where( 'pinned', true )
            ->with( 'galleries', 'tax_group.taxes' )
            ->onSale()
            ->where( function ( Builder $query ): void {
                $this->applyStockVisibility( $query );
            } )
            ->trackingDisabled()
            ->get()
            ->map( function ( Product $product ): Product {
                if ( $product->unit_quantities()->where( 'visible', true )->count() === 1 ) {
                    $product->load( [
                        'unit_quantities' => function ( $query ): void {
                            $query->where( 'visible', true )->with( 'unit' );
                        },
                    ] );
                }

                return $product;
            } );
    }

    private function applyStockVisibility( Builder $query ): void
    {
        if ( ns()->option->get( 'ns_pos_hide_exhausted_products' ) !== 'yes' ) {
            return;
        }

        $query->where( 'stock_management', Product::STOCK_MANAGEMENT_DISABLED )
            ->orWhere( function ( Builder $query ): void {
                $query->where( 'stock_management', Product::STOCK_MANAGEMENT_ENABLED )
                    ->whereHas( 'unit_quantities', function ( Builder $query ): void {
                        $query->where( 'quantity', '>', 0 );
                    } );
            } );
    }
}
