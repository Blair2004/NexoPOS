<?php

namespace Modules\NsOxen\Services;

use App\Models\CustomerGroup;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductUnitQuantity;
use App\Models\Role;
use App\Models\TaxGroup;
use App\Models\Unit;
use App\Models\UnitGroup;
use App\Services\CustomerService;
use App\Services\BarcodeService;
use App\Services\ProductCategoryService;
use App\Services\ProductService;
use App\Services\ProviderService;
use App\Services\UnitService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CatalogCreationService
{
    private const ADDRESS_FIELDS = [
        'first_name',
        'last_name',
        'phone',
        'address_1',
        'address_2',
        'country',
        'city',
        'pobox',
        'company',
        'email',
    ];

    public function __construct(
        private readonly ProductService $productService,
        private readonly BarcodeService $barcodeService,
        private readonly ProductCategoryService $categoryService,
        private readonly UnitService $unitService,
        private readonly ProviderService $providerService,
        private readonly CustomerService $customerService,
    ) {}

    /** @return array<string, mixed> */
    public function createProduct( array $input ): array
    {
        return DB::transaction( function () use ( $input ): array {
            $data = $this->productData( $input );
            $this->validateProductReferences( $data );
            $result = $this->productService->create( $data );
            /** @var Product $product */
            $product = data_get( $result, 'data.product' );
            $this->completeGeneratedPluValues( $product, $data['units']['selling_group'] );

            return $this->productResult( $product->fresh( ['unit_quantities'] ) );
        }, attempts: 3 );
    }

    /** @return array<string, mixed> */
    public function createProvider( array $input ): array
    {
        $fields = Arr::only( $input, ['first_name', 'last_name', 'email', 'phone', 'address_1', 'address_2', 'description'] );
        $result = $this->providerService->create( $fields );
        $provider = data_get( $result, 'data.provider' );

        return $provider->only( ['id', 'first_name', 'last_name', 'email', 'phone', 'address_1', 'address_2', 'description'] );
    }

    /** @return array<string, mixed> */
    public function createCustomer( array $input ): array
    {
        $groupId = (int) ( $input['group_id'] ?? ns()->option->get( 'ns_customers_default_group', 0 ) );
        if ( ! CustomerGroup::query()->whereKey( $groupId )->exists() ) {
            throw new OxenException( 'VALIDATION_FAILED', __m( 'The selected customer group does not exist in the current store.', 'NsOxen' ) );
        }
        if ( ! Role::namespace( Role::STORECUSTOMER ) ) {
            throw new OxenException( 'VALIDATION_FAILED', __m( 'The Store Customer role is not configured.', 'NsOxen' ) );
        }

        $email = trim( (string) ( $input['email'] ?? '' ) );
        if ( $email === '' ) {
            $email = 'customer-' . Str::lower( (string) Str::uuid() ) . '@nexopos.local';
        }
        $fields = Arr::only( $input, [
            'first_name', 'last_name', 'phone', 'pobox', 'gender', 'birth_date',
            'credit_limit_amount', 'description', 'active',
        ] );
        $fields['email'] = $email;
        $fields['username'] = $email;
        $fields['password'] = Hash::make( Str::random( 40 ) );
        $fields['group_id'] = $groupId;
        $fields['active'] = (bool) ( $fields['active'] ?? true );
        $fields['address'] = collect( $input['address'] ?? [] )
            ->only( ['billing', 'shipping'] )
            ->map( fn ( array $address ): array => Arr::only( $address, self::ADDRESS_FIELDS ) )
            ->all();

        $result = $this->customerService->create( $fields );
        $customer = data_get( $result, 'data.customer' );

        return $customer->only( ['id', 'first_name', 'last_name', 'email', 'phone', 'group_id', 'active'] );
    }

    /** @return array<string, mixed> */
    public function importProducts( array $input ): array
    {
        $this->preflightImport( $input );

        return DB::transaction( function () use ( $input ): array {
            Product::query()->orderBy( 'id' )->lockForUpdate()->get( ['id', 'pinned'] );
            ProductCategory::query()->orderBy( 'id' )->lockForUpdate()->get( ['id'] );
            UnitGroup::query()->orderBy( 'id' )->lockForUpdate()->get( ['id'] );
            Unit::query()->orderBy( 'id' )->lockForUpdate()->get( ['id'] );

            $rows = [];
            $createdCategories = [];
            $createdUnitGroups = [];
            $createdUnits = [];

            foreach ( $input['products'] as $index => $row ) {
                $categoryExisted = isset( $row['category']['id'] )
                    ? ProductCategory::query()->whereKey( $row['category']['id'] )->exists()
                    : ProductCategory::query()->whereRaw( 'LOWER(name) = ?', [mb_strtolower( (string) $row['category']['name'] )] )->exists();
                $category = $this->resolveCategory( $row['category'] );
                if ( ! $categoryExisted ) {
                    $createdCategories[$category->id] = $category->id;
                }
                $unitGroupExisted = isset( $row['unit_group']['id'] )
                    ? UnitGroup::query()->whereKey( $row['unit_group']['id'] )->exists()
                    : UnitGroup::query()->whereRaw( 'LOWER(name) = ?', [mb_strtolower( (string) $row['unit_group']['name'] )] )->exists();
                $unitGroup = $this->resolveUnitGroup( $row['unit_group'] );
                if ( ! $unitGroupExisted ) {
                    $createdUnitGroups[$unitGroup->id] = $unitGroup->id;
                }

                $productInput = $row;
                $productInput['category_id'] = $category->id;
                $productInput['unit_group_id'] = $unitGroup->id;
                $productInput['units'] = collect( $row['units'] )->map( function ( array $unitInput ) use ( $unitGroup, &$createdUnits ): array {
                    $unitExisted = isset( $unitInput['unit_id'] )
                        ? Unit::query()->where( 'group_id', $unitGroup->id )->whereKey( $unitInput['unit_id'] )->exists()
                        : Unit::query()->where( 'group_id', $unitGroup->id )->whereRaw( 'LOWER(identifier) = ?', [mb_strtolower( (string) ( $unitInput['identifier'] ?? Str::slug( $unitInput['name'] ) ) )] )->exists();
                    $unit = $this->resolveUnit( $unitGroup, $unitInput );
                    if ( ! $unitExisted ) {
                        $createdUnits[$unit->id] = $unit->id;
                    }

                    return [...$unitInput, 'unit_id' => $unit->id];
                } )->all();

                $data = $this->productData( $productInput );
                $this->validateProductReferences( $data );
                $result = $this->productService->create( $data );
                /** @var Product $product */
                $product = data_get( $result, 'data.product' );
                $this->completeGeneratedPluValues( $product, $data['units']['selling_group'] );
                $rows[] = [
                    'reference' => (string) ( $row['reference'] ?? $index + 1 ),
                    ...$this->productResult( $product->fresh( ['unit_quantities'] ) ),
                ];
            }

            return [
                'created_count' => count( $rows ),
                'created_category_ids' => array_values( $createdCategories ),
                'created_unit_group_ids' => array_values( $createdUnitGroups ),
                'created_unit_ids' => array_values( $createdUnits ),
                'products' => $rows,
            ];
        }, attempts: 3 );
    }

    public function preflightImport( array $input ): void
    {
        $rows = collect( $input['products'] ?? [] );
        $barcodes = $rows->pluck( 'barcode' )->filter()->map( fn ( mixed $value ): string => mb_strtolower( (string) $value ) );
        $skus = $rows->pluck( 'sku' )->filter()->map( fn ( mixed $value ): string => mb_strtolower( (string) $value ) );
        $unitBarcodes = $rows->flatMap( fn ( array $row ): array => collect( $row['units'] )->pluck( 'barcode' )->filter()->all() )->map( fn ( mixed $value ): string => mb_strtolower( (string) $value ) );
        $scalePluValues = $rows->flatMap( fn ( array $row ): array => collect( $row['units'] )->pluck( 'scale_plu' )->filter()->all() )
            ->map( fn ( mixed $value ): string => $this->normalizeScalePlu( $value ) );
        $unitIdentifiers = $rows->flatMap( fn ( array $row ): array => collect( $row['units'] )->filter( fn ( array $unit ): bool => (bool) ( $unit['create_if_missing'] ?? false ) )->map( fn ( array $unit ): string => mb_strtolower( (string) ( $unit['identifier'] ?? Str::slug( $unit['name'] ?? '' ) ) ) )->filter()->all() );
        if ( $barcodes->duplicates()->isNotEmpty() || $skus->duplicates()->isNotEmpty() ) {
            throw new OxenException( 'VALIDATION_FAILED', __m( 'The import contains duplicate product barcodes or SKUs.', 'NsOxen' ) );
        }
        if ( $unitBarcodes->duplicates()->isNotEmpty() || $scalePluValues->duplicates()->isNotEmpty() || $unitIdentifiers->duplicates()->isNotEmpty() ) {
            throw new OxenException( 'VALIDATION_FAILED', __m( 'The import contains duplicate unit barcodes, PLUs, or new unit identifiers.', 'NsOxen' ) );
        }
        if ( $barcodes->isNotEmpty() && Product::query()->whereIn( DB::raw( 'LOWER(barcode)' ), $barcodes->all() )->exists() ) {
            throw new OxenException( 'CONFLICT', __m( 'One or more imported product barcodes already exist.', 'NsOxen' ), 409 );
        }
        if ( $skus->isNotEmpty() && Product::query()->whereIn( DB::raw( 'LOWER(sku)' ), $skus->all() )->exists() ) {
            throw new OxenException( 'CONFLICT', __m( 'One or more imported product SKUs already exist.', 'NsOxen' ), 409 );
        }
        if ( $unitBarcodes->isNotEmpty() && ProductUnitQuantity::query()->whereIn( DB::raw( 'LOWER(barcode)' ), $unitBarcodes->all() )->exists() ) {
            throw new OxenException( 'CONFLICT', __m( 'One or more imported unit barcodes already exist.', 'NsOxen' ), 409 );
        }
        if ( $scalePluValues->isNotEmpty() && ProductUnitQuantity::query()->whereIn( 'scale_plu', $scalePluValues->all() )->exists() ) {
            throw new OxenException( 'CONFLICT', __m( 'One or more imported scale PLUs already exist.', 'NsOxen' ), 409 );
        }
        if ( $unitIdentifiers->isNotEmpty() && Unit::query()->whereIn( DB::raw( 'LOWER(identifier)' ), $unitIdentifiers->all() )->exists() ) {
            throw new OxenException( 'CONFLICT', __m( 'One or more imported unit identifiers already exist.', 'NsOxen' ), 409 );
        }

        $taxGroupIds = $rows->pluck( 'tax_group_id' )->filter()->unique()->values();
        if ( $taxGroupIds->isNotEmpty() && TaxGroup::query()->whereIn( 'id', $taxGroupIds )->count() !== $taxGroupIds->count() ) {
            throw new OxenException( 'VALIDATION_FAILED', __m( 'One or more imported tax groups do not exist in the current store.', 'NsOxen' ) );
        }

        $pinnedRequested = $rows->where( 'pinned', true )->count();
        $maximumPinned = (int) ns()->option->get( 'ns_pos_max_pinned_products', 5 );
        if ( Product::query()->where( 'pinned', true )->count() + $pinnedRequested > $maximumPinned ) {
            throw new OxenException( 'VALIDATION_FAILED', sprintf( __m( 'The import would exceed the limit of %s pinned products.', 'NsOxen' ), $maximumPinned ) );
        }

        foreach ( $rows as $index => $row ) {
            $this->preflightDependency( ProductCategory::query(), $row['category'], 'category', $index );
            $this->preflightDependency( UnitGroup::query(), $row['unit_group'], 'unit group', $index );
            $unitGroup = isset( $row['unit_group']['id'] )
                ? UnitGroup::query()->find( $row['unit_group']['id'] )
                : UnitGroup::query()->whereRaw( 'LOWER(name) = ?', [mb_strtolower( (string) ( $row['unit_group']['name'] ?? '' ) )] )->first();
            $unitIds = collect( $row['units'] )->pluck( 'unit_id' )->filter();
            if ( $unitIds->duplicates()->isNotEmpty() ) {
                throw new OxenException( 'VALIDATION_FAILED', sprintf( __m( 'Import row %s assigns the same selling unit more than once.', 'NsOxen' ), $index + 1 ) );
            }
            foreach ( $row['units'] as $unit ) {
                if ( isset( $unit['unit_id'] ) && ! Unit::query()->whereKey( $unit['unit_id'] )->exists() ) {
                    throw new OxenException( 'VALIDATION_FAILED', sprintf( __m( 'Import row %s references a unit that does not exist.', 'NsOxen' ), $index + 1 ) );
                }
                if ( isset( $unit['unit_id'] ) && $unitGroup && ! Unit::query()->whereKey( $unit['unit_id'] )->where( 'group_id', $unitGroup->id )->exists() ) {
                    throw new OxenException( 'VALIDATION_FAILED', sprintf( __m( 'Import row %s assigns a selling unit from another unit group.', 'NsOxen' ), $index + 1 ) );
                }
            }
        }
    }

    /** @return array<string, mixed> */
    private function productData( array $input ): array
    {
        $barcodeType = $input['barcode_type'] ?? ns()->option->get( 'ns_pos_default_barcode_type', 'code128' );

        return [
            'name' => trim( (string) $input['name'] ),
            'category_id' => (int) $input['category_id'],
            'barcode' => $input['barcode'] ?? null,
            'sku' => $input['sku'] ?? null,
            'barcode_type' => $barcodeType,
            'type' => $input['type'] ?? Product::TYPE_MATERIALIZED,
            'status' => $input['status'] ?? Product::STATUS_AVAILABLE,
            'stock_management' => $input['stock_management'] ?? Product::STOCK_MANAGEMENT_ENABLED,
            'pinned' => (bool) ( $input['pinned'] ?? false ),
            'description' => $input['description'] ?? '',
            'product_type' => 'product',
            'expires' => (bool) ( $input['expires'] ?? false ),
            'on_expiration' => $input['on_expiration'] ?? Product::EXPIRES_PREVENT_SALES,
            'tax_group_id' => $input['tax_group_id'] ?? null,
            'tax_type' => $input['tax_type'] ?? 'inclusive',
            'units' => [
                'unit_group' => (int) $input['unit_group_id'],
                'accurate_tracking' => (bool) ( $input['accurate_tracking'] ?? false ),
                'auto_cogs' => (bool) ( $input['auto_cogs'] ?? false ),
                'selling_group' => collect( $input['units'] )->map( fn ( array $unit ): array => [
                    'unit_id' => (int) $unit['unit_id'],
                    'convert_unit_id' => $unit['convert_unit_id'] ?? null,
                    'sale_price_edit' => (float) $unit['sale_price'],
                    'wholesale_price_edit' => (float) ( $unit['wholesale_price'] ?? $unit['sale_price'] ),
                    'barcode' => $unit['barcode'] ?? $this->barcodeService->generateRandomBarcode( $barcodeType ),
                    'cogs' => (float) ( $unit['cogs'] ?? 0 ),
                    'low_quantity' => (float) ( $unit['low_quantity'] ?? 0 ),
                    'stock_alert_enabled' => (bool) ( $unit['stock_alert_enabled'] ?? false ),
                    'visible' => (bool) ( $unit['visible'] ?? true ),
                    'is_weighable' => (bool) ( $unit['is_weighable'] ?? false ),
                    'scale_plu' => filled( $unit['scale_plu'] ?? null ) ? $this->normalizeScalePlu( $unit['scale_plu'] ) : null,
                ] )->all(),
            ],
            'groups' => ['product_subitems' => []],
            'images' => [],
        ];
    }

    /** @param array<string, mixed> $data */
    private function validateProductReferences( array $data ): void
    {
        $category = ProductCategory::query()->find( $data['category_id'] );
        $unitGroup = UnitGroup::query()->find( $data['units']['unit_group'] );
        if ( ! $category || ! $unitGroup ) {
            throw new OxenException( 'VALIDATION_FAILED', __m( 'The product category or unit group does not exist in the current store.', 'NsOxen' ) );
        }
        if ( $data['tax_group_id'] !== null && ! TaxGroup::query()->whereKey( $data['tax_group_id'] )->exists() ) {
            throw new OxenException( 'VALIDATION_FAILED', __m( 'The selected tax group does not exist in the current store.', 'NsOxen' ) );
        }

        $unitIds = collect( $data['units']['selling_group'] )->pluck( 'unit_id' );
        $matchingUnits = Unit::query()->where( 'group_id', $unitGroup->id )->whereIn( 'id', $unitIds )->count();
        if ( $matchingUnits !== $unitIds->unique()->count() ) {
            throw new OxenException( 'VALIDATION_FAILED', __m( 'Every selling unit must exist and belong to the selected unit group.', 'NsOxen' ) );
        }
        $convertUnitIds = collect( $data['units']['selling_group'] )->pluck( 'convert_unit_id' )->filter()->unique();
        if ( $convertUnitIds->isNotEmpty() && Unit::query()->where( 'group_id', $unitGroup->id )->whereIn( 'id', $convertUnitIds )->count() !== $convertUnitIds->count() ) {
            throw new OxenException( 'VALIDATION_FAILED', __m( 'Every conversion unit must belong to the selected unit group.', 'NsOxen' ) );
        }
        $unitBarcodes = collect( $data['units']['selling_group'] )->pluck( 'barcode' )->filter()->map( fn ( mixed $value ): string => mb_strtolower( (string) $value ) );
        $scalePluValues = collect( $data['units']['selling_group'] )->pluck( 'scale_plu' )->filter();
        if ( $unitBarcodes->duplicates()->isNotEmpty() || $scalePluValues->duplicates()->isNotEmpty() ) {
            throw new OxenException( 'VALIDATION_FAILED', __m( 'Selling unit barcodes and scale PLUs must be unique within a product.', 'NsOxen' ) );
        }
        if ( $unitBarcodes->isNotEmpty() && ProductUnitQuantity::query()->whereIn( DB::raw( 'LOWER(barcode)' ), $unitBarcodes )->exists() ) {
            throw new OxenException( 'CONFLICT', __m( 'A selling unit barcode is already assigned to another product.', 'NsOxen' ), 409 );
        }

        foreach ( $data['units']['selling_group'] as $unit ) {
            if ( ! $unit['is_weighable'] ) {
                continue;
            }
            if ( ns()->option->get( 'ns_scale_barcode_enabled', 'no' ) !== 'yes' || ! $category->scaleRange ) {
                throw new OxenException( 'VALIDATION_FAILED', __m( 'Scale barcode support and a category PLU range are required for weighable units.', 'NsOxen' ) );
            }
            if ( filled( $unit['scale_plu'] ) ) {
                $plu = $this->productService->validateAndFormatPLU( (string) $unit['scale_plu'] );
                if ( ! $category->scaleRange->containsPLU( $plu ) || ! $this->productService->isPLUUnique( $plu ) ) {
                    throw new OxenException( 'VALIDATION_FAILED', __m( 'A supplied scale PLU is invalid, outside the category range, or already used.', 'NsOxen' ) );
                }
            }
        }
    }

    /** @param array<int, array<string, mixed>> $sellingUnits */
    private function completeGeneratedPluValues( Product $product, array $sellingUnits ): void
    {
        foreach ( $sellingUnits as $unit ) {
            if ( ! $unit['is_weighable'] || filled( $unit['scale_plu'] ) ) {
                continue;
            }
            $quantity = ProductUnitQuantity::query()->where( 'product_id', $product->id )->where( 'unit_id', $unit['unit_id'] )->lockForUpdate()->firstOrFail();
            if ( filled( $quantity->scale_plu ) ) {
                continue;
            }
            $quantity->scale_plu = $this->productService->generateScalePLU( $product->id, $quantity->id );
            $quantity->save();
        }
    }

    private function normalizeScalePlu( mixed $value ): string
    {
        try {
            return $this->productService->validateAndFormatPLU( (string) $value );
        } catch ( \Throwable $exception ) {
            throw new OxenException( 'VALIDATION_FAILED', $exception->getMessage() );
        }
    }

    private function resolveCategory( array $reference ): ProductCategory
    {
        $category = isset( $reference['id'] )
            ? ProductCategory::query()->find( $reference['id'] )
            : ProductCategory::query()->whereRaw( 'LOWER(name) = ?', [mb_strtolower( (string) $reference['name'] )] )->first();
        if ( $category ) {
            return $category;
        }
        if ( ! ( $reference['create_if_missing'] ?? false ) ) {
            throw new OxenException( 'VALIDATION_FAILED', __m( 'An imported product category could not be resolved.', 'NsOxen' ) );
        }

        return data_get( $this->categoryService->create( Arr::only( $reference, ['name', 'description', 'parent_id'] ) ), 'data.category' );
    }

    private function resolveUnitGroup( array $reference ): UnitGroup
    {
        $group = isset( $reference['id'] )
            ? UnitGroup::query()->find( $reference['id'] )
            : UnitGroup::query()->whereRaw( 'LOWER(name) = ?', [mb_strtolower( (string) $reference['name'] )] )->first();
        if ( $group ) {
            return $group;
        }
        if ( ! ( $reference['create_if_missing'] ?? false ) ) {
            throw new OxenException( 'VALIDATION_FAILED', __m( 'An imported unit group could not be resolved.', 'NsOxen' ) );
        }

        return data_get( $this->unitService->createGroup( Arr::only( $reference, ['name', 'description'] ) ), 'data.group' );
    }

    private function resolveUnit( UnitGroup $group, array $reference ): Unit
    {
        $unit = isset( $reference['unit_id'] )
            ? Unit::query()->where( 'group_id', $group->id )->find( $reference['unit_id'] )
            : Unit::query()->where( 'group_id', $group->id )->whereRaw( 'LOWER(identifier) = ?', [mb_strtolower( (string) ( $reference['identifier'] ?? Str::slug( $reference['name'] ) ) )] )->first();
        if ( $unit ) {
            return $unit;
        }
        if ( ! ( $reference['create_if_missing'] ?? false ) ) {
            throw new OxenException( 'VALIDATION_FAILED', __m( 'An imported selling unit could not be resolved.', 'NsOxen' ) );
        }

        return data_get( $this->unitService->createUnit( [
            'name' => $reference['name'],
            'identifier' => $reference['identifier'] ?? Str::slug( $reference['name'] ),
            'value' => (float) ( $reference['value'] ?? 1 ),
            'group_id' => $group->id,
            'base_unit' => (bool) ( $reference['base_unit'] ?? false ),
            'description' => $reference['description'] ?? '',
        ] ), 'data.unit' );
    }

    private function preflightDependency( $query, array $reference, string $label, int $index ): void
    {
        $exists = isset( $reference['id'] )
            ? ( clone $query )->whereKey( $reference['id'] )->exists()
            : ( clone $query )->whereRaw( 'LOWER(name) = ?', [mb_strtolower( (string) ( $reference['name'] ?? '' ) )] )->exists();
        if ( ! $exists && ! ( $reference['create_if_missing'] ?? false ) ) {
            throw new OxenException( 'VALIDATION_FAILED', sprintf( __m( 'Import row %1$s references a %2$s that does not exist.', 'NsOxen' ), $index + 1, $label ) );
        }
    }

    /** @return array<string, mixed> */
    private function productResult( Product $product ): array
    {
        return [
            'product_id' => (int) $product->id,
            'name' => (string) $product->name,
            'sku' => (string) $product->sku,
            'barcode' => (string) $product->barcode,
            'category_id' => (int) $product->category_id,
            'unit_quantity_ids' => $product->unit_quantities->pluck( 'id' )->map( fn ( mixed $id ): int => (int) $id )->all(),
        ];
    }
}
