<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\TaxGroup;
use App\Models\UnitGroup;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ProductGeneratorService
{
    /**
     * @var list<string>
     */
    private const GENERIC_IMAGES = [
        'images/products/furnitures.jpg',
        'images/products/kitchen-dinning.jpg',
        'images/products/decor.jpg',
        'images/products/comferter-sets.jpg',
    ];

    /** @var list<string> */
    private const ADJECTIVES = [
        'Classic', 'Modern', 'Essential', 'Premium', 'Compact', 'Deluxe', 'Everyday', 'Urban', 'Natural', 'Signature',
        'Smart', 'Comfort', 'Artisan', 'Eco', 'Professional', 'Portable', 'Elegant', 'Practical', 'Advanced', 'Fresh',
    ];

    /** @var list<string> */
    private const COLORS = [
        'Amber', 'Azure', 'Black', 'Blue', 'Bronze', 'Coral', 'Cream', 'Emerald', 'Gold', 'Graphite',
        'Green', 'Ivory', 'Navy', 'Olive', 'Orange', 'Red', 'Rose', 'Silver', 'Teal', 'White',
    ];

    /** @var list<string> */
    private const PRODUCT_NAMES = [
        'Backpack', 'Basket', 'Bottle', 'Bowl', 'Cabinet', 'Chair', 'Clock', 'Container', 'Desk', 'Kettle',
        'Lamp', 'Mirror', 'Mug', 'Organizer', 'Pan', 'Pillow', 'Shelf', 'Table', 'Towel', 'Tray',
        'Vase', 'Wallet', 'Watch', 'Speaker', 'Notebook',
    ];

    public function __construct(
        private readonly ProductService $productService,
    ) {}

    /**
     * @param Collection<int, ProductCategory> $categories
     * @param callable(int): void|null         $afterProductCreated
     */
    public function generate(
        int $count,
        Collection $categories,
        UnitGroup $unitGroup,
        User $author,
        ?TaxGroup $taxGroup = null,
        ?callable $afterProductCreated = null,
    ): int {
        if ( $count < 1 ) {
            throw new InvalidArgumentException( 'The product count must be at least one.' );
        }

        if ( $categories->isEmpty() ) {
            throw new InvalidArgumentException( 'At least one product category is required.' );
        }

        if ( ! $unitGroup->relationLoaded( 'units' ) ) {
            $unitGroup->load( 'units' );
        }

        if ( $unitGroup->units->isEmpty() ) {
            throw new InvalidArgumentException( 'The selected unit group must contain at least one unit.' );
        }

        $runIdentifier = Str::lower( (string) Str::ulid() );

        for ( $index = 1; $index <= $count; $index++ ) {
            $salePrice = round( 2.5 + ( ( $index * 7919 ) % 99750 ) / 100, 2 );
            $wholesalePrice = round( $salePrice * ( 0.55 + ( $index % 21 ) / 100 ), 2 );
            $category = $categories->get( ( $index - 1 ) % $categories->count() );
            $image = self::GENERIC_IMAGES[( $index - 1 ) % count( self::GENERIC_IMAGES )];
            $identifier = sprintf( '%s-%05d', $runIdentifier, $index );

            $this->productService->create( [
                'product_type' => 'product',
                'name' => $this->makeProductName( $index ),
                'sku' => 'GEN-SKU-' . $identifier,
                'barcode' => 'GEN-' . $identifier,
                'barcode_type' => 'code128',
                'category_id' => $category->id,
                'description' => __( 'Generated sample product.' ),
                'type' => Product::TYPE_MATERIALIZED,
                'status' => 'available',
                'stock_management' => 'disabled',
                'tax_group_id' => $taxGroup?->id,
                'tax_type' => $taxGroup instanceof TaxGroup ? 'inclusive' : null,
                'author_id' => $author->id,
                'images' => [
                    [
                        'featured' => true,
                        'url' => asset( $image ),
                    ],
                ],
                'units' => [
                    'unit_group' => $unitGroup->id,
                    'selling_group' => $unitGroup->units->map( static fn ( $unit ): array => [
                        'sale_price_edit' => $salePrice * (float) $unit->value,
                        'wholesale_price_edit' => $wholesalePrice * (float) $unit->value,
                        'unit_id' => $unit->id,
                        'preview_url' => asset( $image ),
                    ] )->all(),
                ],
            ] );

            if ( $afterProductCreated !== null ) {
                $afterProductCreated( $index );
            }
        }

        return $count;
    }

    private function makeProductName( int $index ): string
    {
        $zeroBasedIndex = $index - 1;
        $adjective = self::ADJECTIVES[$zeroBasedIndex % count( self::ADJECTIVES )];
        $color = self::COLORS[intdiv( $zeroBasedIndex, count( self::ADJECTIVES ) ) % count( self::COLORS )];
        $product = self::PRODUCT_NAMES[intdiv( $zeroBasedIndex, count( self::ADJECTIVES ) * count( self::COLORS ) ) % count( self::PRODUCT_NAMES )];

        return sprintf( '%s %s %s %05d', $adjective, $color, $product, $index );
    }
}
