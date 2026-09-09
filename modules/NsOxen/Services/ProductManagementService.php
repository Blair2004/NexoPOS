<?php

namespace Modules\NsOxen\Services;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductUnitQuantity;
use App\Models\TaxGroup;
use App\Services\ProductService;
use App\Services\TaxService;
use Illuminate\Support\Collection;
use Throwable;

class ProductManagementService
{
    private const PRODUCT_FIELDS = [
        'category_id',
        'auto_cogs',
        'tax_group_id',
        'tax_type',
        'expires',
        'on_expiration',
        'barcode_type',
        'type',
        'status',
        'stock_management',
        'pinned',
    ];

    public function __construct(
        private readonly ProductService $productService,
        private readonly TaxService $taxService,
    ) {}

    /** @return array{updated_count: int, product_ids: list<int>} */
    public function updateProducts( array $input ): array
    {
        $productIds = collect( $input['product_ids'] )->map( static fn ( mixed $id ): int => (int) $id )->unique()->sort()->values();
        $changes = $input['changes'];
        $products = Product::query()->whereIn( 'id', $productIds )->orderBy( 'id' )->lockForUpdate()->get();

        $this->ensureEveryTargetExists( $productIds, $products, __m( 'One or more products were not found in the current store.', 'NsOxen' ) );
        $this->validateProductChanges( $products, $changes );

        $taxChanged = array_key_exists( 'tax_group_id', $changes ) || array_key_exists( 'tax_type', $changes );
        foreach ( $products as $product ) {
            foreach ( self::PRODUCT_FIELDS as $field ) {
                if ( array_key_exists( $field, $changes ) ) {
                    $product->{$field} = $changes[$field];
                }
            }
            $product->save();

            if ( $taxChanged ) {
                $product->unit_quantities()->orderBy( 'id' )->lockForUpdate()->get()->each(
                    fn ( ProductUnitQuantity $unitQuantity ) => $this->taxService->computeTax( $unitQuantity, $product->tax_group_id, $product->tax_type ),
                );
            }
        }

        return ['updated_count' => $products->count(), 'product_ids' => $productIds->all()];
    }

    /** @return array{updated_count: int, unit_quantity_ids: list<int>} */
    public function updateProductUnitQuantities( array $input ): array
    {
        $unitQuantityIds = collect( $input['unit_quantity_ids'] )->map( static fn ( mixed $id ): int => (int) $id )->unique()->sort()->values();
        $changes = $input['changes'];
        $unitQuantities = ProductUnitQuantity::query()
            ->with( ['product.category.scaleRange'] )
            ->whereIn( 'id', $unitQuantityIds )
            ->orderBy( 'id' )
            ->lockForUpdate()
            ->get();

        $this->ensureEveryTargetExists( $unitQuantityIds, $unitQuantities, __m( 'One or more product unit quantities were not found in the current store.', 'NsOxen' ) );
        if ( array_key_exists( 'scale_plu', $changes ) && $unitQuantities->count() > 1 ) {
            throw new OxenException( 'VALIDATION_FAILED', __m( 'A supplied scale PLU can only be assigned to one product unit.', 'NsOxen' ) );
        }

        foreach ( $unitQuantities as $unitQuantity ) {
            $finalIsWeighable = array_key_exists( 'is_weighable', $changes )
                ? (bool) $changes['is_weighable']
                : (bool) $unitQuantity->is_weighable;

            if ( array_key_exists( 'scale_plu', $changes ) && ! $finalIsWeighable ) {
                throw new OxenException( 'VALIDATION_FAILED', __m( 'A scale PLU can only be supplied for a weighable product unit.', 'NsOxen' ) );
            }

            $scalePlu = $unitQuantity->scale_plu;
            $weighingChanged = array_key_exists( 'is_weighable', $changes ) || array_key_exists( 'scale_plu', $changes );
            if ( $finalIsWeighable && $weighingChanged ) {
                $scalePlu = $this->validatedScalePlu( $unitQuantity, $changes['scale_plu'] ?? $scalePlu );
            }

            if ( array_key_exists( 'is_weighable', $changes ) ) {
                $unitQuantity->is_weighable = $finalIsWeighable;
            }
            if ( $finalIsWeighable && $weighingChanged ) {
                $unitQuantity->scale_plu = $scalePlu;
            }
            if ( array_key_exists( 'stock_alert_enabled', $changes ) ) {
                $unitQuantity->stock_alert_enabled = (bool) $changes['stock_alert_enabled'];
            }
            if ( array_key_exists( 'visible', $changes ) ) {
                $unitQuantity->visible = (bool) $changes['visible'];
            }
            $unitQuantity->save();
        }

        return ['updated_count' => $unitQuantities->count(), 'unit_quantity_ids' => $unitQuantityIds->all()];
    }

    /** @param Collection<int, Product> $products */
    private function validateProductChanges( Collection $products, array $changes ): void
    {
        if ( array_key_exists( 'category_id', $changes ) && ! ProductCategory::query()->whereKey( $changes['category_id'] )->exists() ) {
            throw new OxenException( 'VALIDATION_FAILED', __m( 'The selected product category does not exist in the current store.', 'NsOxen' ) );
        }
        if ( array_key_exists( 'tax_group_id', $changes ) && $changes['tax_group_id'] !== null && ! TaxGroup::query()->whereKey( $changes['tax_group_id'] )->exists() ) {
            throw new OxenException( 'VALIDATION_FAILED', __m( 'The selected tax group does not exist in the current store.', 'NsOxen' ) );
        }
        if ( array_key_exists( 'type', $changes ) && $products->contains( fn ( Product $product ): bool => $product->type === Product::TYPE_GROUPED ) ) {
            throw new OxenException( 'VALIDATION_FAILED', __m( 'Grouped products cannot be converted to another product type.', 'NsOxen' ) );
        }
        if ( array_key_exists( 'pinned', $changes ) ) {
            $allProducts = Product::query()->orderBy( 'id' )->lockForUpdate()->get( ['id', 'pinned'] );
            $targetIds = $products->pluck( 'id' );
            $finalPinnedCount = $allProducts->whereNotIn( 'id', $targetIds )->where( 'pinned', true )->count()
                + ( (bool) $changes['pinned'] ? $products->count() : 0 );
            $maximumPinned = (int) ns()->option->get( 'ns_pos_max_pinned_products', 5 );
            if ( $finalPinnedCount > $maximumPinned ) {
                throw new OxenException( 'VALIDATION_FAILED', sprintf( __m( 'You cannot pin more than %s products.', 'NsOxen' ), $maximumPinned ) );
            }
        }
    }

    private function validatedScalePlu( ProductUnitQuantity $unitQuantity, mixed $requestedScalePlu ): string
    {
        if ( ns()->option->get( 'ns_scale_barcode_enabled', 'no' ) !== 'yes' ) {
            throw new OxenException( 'VALIDATION_FAILED', __m( 'Scale barcode support must be enabled before a product unit can be weighable.', 'NsOxen' ) );
        }

        $scaleRange = $unitQuantity->product?->category?->scaleRange;
        if ( ! $scaleRange ) {
            throw new OxenException( 'VALIDATION_FAILED', __m( 'The product category must have a PLU range before weighing can be enabled.', 'NsOxen' ) );
        }
        $scaleRange = $unitQuantity->product->category->scaleRange()->lockForUpdate()->first();

        try {
            if ( is_string( $requestedScalePlu ) && $requestedScalePlu !== '' ) {
                if ( preg_match( '/^\d+$/', $requestedScalePlu ) !== 1 ) {
                    throw new OxenException( 'VALIDATION_FAILED', __m( 'Scale PLUs must contain digits only.', 'NsOxen' ) );
                }
                $scalePlu = $this->productService->validateAndFormatPLU( $requestedScalePlu );
                if ( ! $scaleRange->containsPLU( $scalePlu ) ) {
                    throw new OxenException( 'VALIDATION_FAILED', __m( 'The scale PLU is outside the product category range.', 'NsOxen' ) );
                }
                if ( ! $this->productService->isPLUUnique( $scalePlu, $unitQuantity->id ) ) {
                    throw new OxenException( 'VALIDATION_FAILED', __m( 'The scale PLU is already assigned to another product unit.', 'NsOxen' ) );
                }

                return $scalePlu;
            }

            return $this->productService->generateScalePLU( $unitQuantity->product_id, $unitQuantity->id );
        } catch ( OxenException $exception ) {
            throw $exception;
        } catch ( Throwable $exception ) {
            throw new OxenException( 'VALIDATION_FAILED', $exception->getMessage() );
        }
    }

    /** @param Collection<int, int> $ids @param Collection<int, mixed> $models */
    private function ensureEveryTargetExists( Collection $ids, Collection $models, string $message ): void
    {
        if ( $ids->count() !== $models->count() ) {
            throw new OxenException( 'NOT_FOUND', $message, 404 );
        }
    }
}
