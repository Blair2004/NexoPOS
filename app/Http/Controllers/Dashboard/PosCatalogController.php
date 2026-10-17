<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Resources\PosProductResource;
use App\Models\Product;
use App\Services\PosPinnedProductService;
use App\Services\ProductService;
use Illuminate\Http\Request;

class PosCatalogController extends Controller
{
    public function __construct(
        private ProductService $productService,
        private PosPinnedProductService $pinnedProductService
    ) {}

    public function categories( Request $request, ?int $category = null ): array
    {
        $result = app( CategoryController::class )->getCategories( $category === null ? '0' : (string) $category );
        $result['products'] = $this->products( $request, $result['products'] );
        $result['pinnedProducts'] = $this->products( $request, $result['pinnedProducts'] ?? [] );

        return $result;
    }

    public function pinned( Request $request ): array
    {
        return [ 'pinnedProducts' => $this->products( $request, $this->pinnedProductService->get() ) ];
    }

    public function search( Request $request ): array
    {
        return $this->products( $request, $this->productService->searchProduct(
            search: $request->input( 'search' ),
            arguments: (array) $request->input( 'arguments' )
        ) );
    }

    public function barcode( Request $request, string $reference ): array
    {
        $result = app( ProductsController::class )->searchUsingArgument( $reference );
        $result['product'] = PosProductResource::make( $result['product'] )->resolve( $request );
        $selectedId = $result['unitQuantity']->id ?? null;
        $result['unitQuantity'] = collect( $result['product']['unit_quantities'] )->firstWhere( 'id', $selectedId );

        return $result;
    }

    public function units( Request $request, Product $product ): array
    {
        $product->load( 'unit_quantities.unit', 'tax_group.taxes', 'galleries' );

        return PosProductResource::make( $product )->resolve( $request )['unit_quantities'];
    }

    private function products( Request $request, iterable $products ): array
    {
        return collect( $products )->map( function ( Product $product ) use ( $request ): array {
            $product->loadMissing( 'unit_quantities.unit', 'tax_group.taxes', 'galleries' );

            return PosProductResource::make( $product )->resolve( $request );
        } )->values()->all();
    }
}
