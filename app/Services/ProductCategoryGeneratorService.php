<?php

namespace App\Services;

use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ProductCategoryGeneratorService
{
    /**
     * @var list<string>
     */
    private const GENERIC_IMAGES = [
        'images/groceries.png',
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
    private const DEPARTMENTS = [
        'Home', 'Office', 'Kitchen', 'Garden', 'Fashion', 'Sports', 'Electronics', 'Grocery', 'Beauty', 'Automotive',
        'Outdoor', 'Travel', 'Health', 'Kids', 'Pets', 'Books', 'Tools', 'Furniture', 'Decor', 'Accessories',
    ];

    /** @var list<string> */
    private const COLLECTIONS = [
        'Collection', 'Essentials', 'Supplies', 'Selection', 'Goods',
        'Equipment', 'Accessories', 'Favorites', 'Basics', 'Specialties',
    ];

    public function __construct(
        private readonly ProductCategoryService $categoryService,
    ) {}

    /**
     * @param callable(int): void|null $afterCategoryCreated
     */
    public function generate(
        int $count,
        User $author,
        ?ProductCategory $parent = null,
        ?callable $afterCategoryCreated = null,
    ): int {
        if ( $count < 1 ) {
            throw new InvalidArgumentException( 'The category count must be at least one.' );
        }

        $runIdentifier = Str::lower( (string) Str::ulid() );

        for ( $index = 1; $index <= $count; $index++ ) {
            $image = self::GENERIC_IMAGES[( $index - 1 ) % count( self::GENERIC_IMAGES )];

            $this->categoryService->create( [
                'name' => $this->makeCategoryName( $index, $runIdentifier ),
                'description' => __( 'Generated sample product category.' ),
                'preview_url' => asset( $image ),
                'parent_id' => $parent?->id,
                'displays_on_pos' => true,
                'author_id' => $author->id,
            ] );

            if ( $afterCategoryCreated !== null ) {
                $afterCategoryCreated( $index );
            }
        }

        return $count;
    }

    private function makeCategoryName( int $index, string $runIdentifier ): string
    {
        $zeroBasedIndex = $index - 1;
        $adjective = self::ADJECTIVES[$zeroBasedIndex % count( self::ADJECTIVES )];
        $department = self::DEPARTMENTS[intdiv( $zeroBasedIndex, count( self::ADJECTIVES ) ) % count( self::DEPARTMENTS )];
        $collection = self::COLLECTIONS[intdiv( $zeroBasedIndex, count( self::ADJECTIVES ) * count( self::DEPARTMENTS ) ) % count( self::COLLECTIONS )];

        return sprintf( '%s %s %s %05d %s', $adjective, $department, $collection, $index, $runIdentifier );
    }
}
