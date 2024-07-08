<?php

namespace Tests\Traits;

use App\Crud\ProductCrud;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductHistory;
use App\Models\ProductUnitQuantity;
use App\Models\TaxGroup;
use App\Models\UnitGroup;
use App\Services\CurrencyService;
use App\Services\ProductService;
use App\Services\TaxService;
use Exception;
use Illuminate\Support\Str;

trait WithProductTest
{
    protected function attemptSetProduct( $product_id = null, $form = [], $categories = [], $unitGroup = null, $taxType = 'inclusive', $sale_price = null, $skip_tests = false ): array
    {
        /**
         * if no form is provided, then
         * we'll generate the form automatically.
         */

        /**
         * @var TaxService
         */
        $taxService = app()->make( TaxService::class );

        if ( empty( $form ) ) {
            $faker = \Faker\Factory::create();

            $taxType = $taxType ?: $faker->randomElement( [ 'exclusive', 'inclusive' ] );
            $unitGroup = $unitGroup ?: UnitGroup::first();
            $sale_price = $sale_price ?: $faker->numberBetween( 5, 10 );
            $categories = $categories ?: ProductCategory::where( 'parent_id', '>', 0 )
                ->orWhere( 'parent_id', null )
                ->get();

            $category = $faker->randomElement( $categories );

            /**
             * We'll merge with the provided $form
             * and count category from that.
             */
            $form = $form ?: [
                'name' => ucwords( $faker->word ),
                'variations' => [
                    [
                        '$primary' => true,
                        'expiracy' => [
                            'expires' => 0,
                            'on_expiration' => 'prevent_sales',
                        ],
                        'identification' => [
                            'barcode' => $faker->ean13(),
                            'barcode_type' => 'ean13',
                            'searchable' => $faker->randomElement( [ true, false ] ),
                            'category_id' => $category->id,
                            'description' => __( 'Created via tests' ),
                            'product_type' => 'product',
                            'type' => Product::TYPE_MATERIALIZED,
                            'sku' => Str::random( 15 ) . '-sku',
                            'status' => 'available',
                            'stock_management' => 'enabled',
                        ],
                        'images' => [],
                        'taxes' => [
                            'tax_group_id' => 1,
                            'tax_type' => $taxType,
                        ],
                        'units' => [
                            'selling_group' => $unitGroup->units->map( function ( $unit ) use ( $faker, $sale_price ) {
                                return [
                                    'sale_price_edit' => $sale_price,
                                    'wholesale_price_edit' => $faker->numberBetween( 20, 25 ),
                                    'unit_id' => $unit->id,
                                ];
                            } )->toArray(),
                            'unit_group' => $unitGroup->id,
                        ],
                    ],
                ],
            ];
        }

        if ( ! $skip_tests ) {
            $currentCategory = ProductCategory::find( $form[ 'variations' ][0][ 'identification' ][ 'category_id' ] );
            $sale_price = $form[ 'variations' ][0][ 'units' ][ 'selling_group' ][0][ 'sale_price_edit' ];
            $categoryProductCount = $currentCategory->products()->count();
        }

        $response = $this
            ->withSession( $this->app[ 'session' ]->all() )
            ->json( $product_id === null ? 'POST' : 'PUT', '/api/products/' . ( $product_id !== null ? $product_id : '' ), $form );

        $response->assertStatus( 200 );

        if ( ! $skip_tests ) {
            $result = json_decode( $response->getContent(), true );
            $taxGroup = TaxGroup::find( 1 );

            $response->assertStatus( 200 );

            if ( $taxType === 'exclusive' ) {
                $this->assertEquals( (float) data_get( $result, 'data.product.unit_quantities.0.sale_price' ), $taxService->getPriceWithTaxUsingGroup( $taxType, $taxGroup, $sale_price ) );
                $this->assertEquals( (float) data_get( $result, 'data.product.unit_quantities.0.sale_price_with_tax' ), $taxService->getPriceWithTaxUsingGroup( $taxType, $taxGroup, $sale_price ) );
                $this->assertEquals( (float) data_get( $result, 'data.product.unit_quantities.0.sale_price_without_tax' ), $taxService->getPriceWithoutTaxUsingGroup( $taxType, $taxGroup, $sale_price ) );
            } else {
                $this->assertEquals( (float) data_get( $result, 'data.product.unit_quantities.0.sale_price', 0 ), $taxService->getPriceWithTaxUsingGroup( $taxType, $taxGroup, $sale_price ) );
                $this->assertEquals( (float) data_get( $result, 'data.product.unit_quantities.0.sale_price_with_tax', 0 ), $taxService->getPriceWithTaxUsingGroup( $taxType, $taxGroup, $sale_price ) );
                $this->assertEquals( (float) data_get( $result, 'data.product.unit_quantities.0.sale_price_without_tax', 0 ), $taxService->getPriceWithoutTaxUsingGroup( $taxType, $taxGroup, $sale_price ) );
            }

            $currentCategory->refresh();

            $this->assertEquals( $categoryProductCount + 1, $currentCategory->total_items, 'The category total items hasn\'t increased' );
        }

        return $response->json();
    }

    protected function attemptChangeProductCategory()
    {
        $result = $this->attemptSetProduct();

        /**
         * Step 2: let's store the previous category
         * and assign a new category to see if the old category
         * see his total_items count updated.
         */
        $oldCategoryID = $result[ 'data' ][ 'product' ][ 'category_id' ];
        $oldCategory = ProductCategory::find( $oldCategoryID );
        $newCategory = ProductCategory::where( 'id', '!=', $oldCategoryID )
            ->where( 'parent_id', null )
            ->first();

        $productCrud = new ProductCrud;
        $productData = $result[ 'data' ][ 'product' ];
        $product = Product::find( $productData[ 'id' ] );
        $newForm = $productCrud->getExtractedProductForm( $product );

        /**
         * We'll new update
         * the category
         */
        $newForm[ 'variations' ][0][ 'identification' ][ 'category_id' ] = $newCategory->id;

        $newResult = $this->attemptSetProduct(
            product_id: $product->id,
            form: $newForm
        );

        /**
         * Step 3: We'll now check if the previous category has his quantity updated
         */
        $oldCategoryRefreshed = $oldCategory->fresh();
        $newCategoryRefreshed = $newCategory->fresh();

        $this->assertGreaterThan(
            expected: $newCategory->total_items,
            actual: $newCategoryRefreshed->total_items,
            message: sprintf(
                'The new category "total_items" has\nt properly been updated. %s was expected, we have %s currently defined.',
                $oldCategoryRefreshed->total_items,
                $oldCategory->total_items
            )
        );

        $this->assertGreaterThan(
            expected: $oldCategoryRefreshed->total_items,
            actual: $oldCategory->total_items,
            message: sprintf(
                'The old category "total_items" has\nt properly been updated. %s was expected, we have %s currently defined.',
                $oldCategoryRefreshed->total_items,
                $oldCategory->total_items
            )
        );
    }

    protected function orderProduct( $name, $unit_price, $quantity, $unitQuantityId = null, $productId = null, $discountType = null, $discountPercentage = null, $taxType = null, $taxGroupId = null )
    {
        $product = $productId !== null ? Product::with( 'unit_quantities' )->find( $productId ) : Product::with( 'unit_quantities' )->withStockEnabled()->whereHas( 'unit_quantities', fn( $query ) => $query->where( 'quantity', '>', $quantity ) )->get()->random();
        $unitQuantity = $unitQuantityId !== null ? $product->unit_quantities->filter( fn( $unitQuantity ) => (int) $unitQuantity->id === (int) $unitQuantityId )->first() : $product->unit_quantities->first();

        ! $product instanceof Product ? throw new Exception( 'The provided product is not valid.' ) : null;
        ! $unitQuantity instanceof ProductUnitQuantity ? throw new Exception( 'The provided unit quantity is not valid.' ) : null;

        return [
            'name' => $name,
            'unit_price' => $unit_price,
            'quantity' => $quantity,
            'product_id' => $product->id,
            'unit_quantity_id' => $unitQuantity->id,
            'unit_id' => $unitQuantity->unit_id,
            'discount_type' => $discountType,
            'discount_percentage' => $discountPercentage,
            'tax_group_id' => $taxGroupId,
            'tax_type' => $taxType,
        ];
    }

    protected function attemptDeleteProducts()
    {
        $result = $this->attemptSetProduct();

        /**
         * We'll delete the last product and see
         * if the unit quantities are deleted as well.
         *
         * @var ProductService
         */
        $productService = app()->make( ProductService::class );

        $product = Product::find( $result[ 'data' ][ 'product' ][ 'id' ] );
        $category = $product->category;
        $totalItems = $category->total_items;

        $this->assertTrue( $product->unit_quantities()->count() > 0, 'The created product is missing unit quantities.' );

        $productService->deleteProduct( $product );

        $category->refresh();

        $this->assertTrue( ProductUnitQuantity::where( 'product_id', $product->id )->count() === 0, 'The product unit quantities wheren\'t deleted.' );
        $this->assertTrue( ProductHistory::where( 'product_id', $product->id )->count() === 0, 'The product history wasn\'t deleted.' );
        $this->assertTrue( $category->total_items === $totalItems - 1, 'The category total items wasn\'t updated after the deletion.' );
    }

    public function attemptDeleteProduct( $product )
    {
        /**
         * @var ProductService
         */
        $productService = app()->make( ProductService::class );

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'DELETE', '/api/crud/ns.products/' . $product->id );

        $response->assertStatus( 200 );
    }

    protected function attemptCreateGroupedProduct()
    {
        /**
         * @var CurrencyService
         */
        $currency = app()->make( CurrencyService::class );

        $faker = \Faker\Factory::create();

        /**
         * @var TaxService
         */
        $taxService = app()->make( TaxService::class );
        $taxType = $faker->randomElement( [ 'exclusive', 'inclusive' ] );
        $unitGroup = UnitGroup::first();
        $sale_price = $faker->numberBetween( 5, 10 );
        $categories = ProductCategory::where( 'parent_id', '>', 0 )
            ->orWhere( 'parent_id', null )
            ->get()
            ->map( fn( $cat ) => $cat->id )
            ->toArray();

        $products = Product::withStockEnabled()
            ->notInGroup()
            ->notGrouped()
            ->limit( 2 )
            ->get()
            ->map( function ( $product ) use ( $faker ) {
                /**
                 * @var ProductUnitQuantity $unitQuantity
                 */
                $unitQuantity = $product->unit_quantities->first();
                $unitQuantityID = $unitQuantity->id;

                return [
                    'unit_quantity_id' => $unitQuantityID,
                    'product_id' => $product->id,
                    'unit_id' => $unitQuantity->unit->id,
                    'quantity' => $faker->numberBetween( 1, 3 ),
                    'sale_price' => $unitQuantity->sale_price,
                ];
            } )
            ->toArray();

        $this->assertTrue( count( $products ) > 0, __( 'There is no product to create a grouped product' ) );

        $response = $this
            ->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', '/api/products/', [
                'name' => $faker->word,
                'variations' => [
                    [
                        '$primary' => true,
                        'expiracy' => [
                            'expires' => 0,
                            'on_expiration' => 'prevent_sales',
                        ],
                        'identification' => [
                            'barcode' => $faker->ean13(),
                            'barcode_type' => 'ean13',
                            'searchable' => $faker->randomElement( [ true, false ] ),
                            'category_id' => $faker->randomElement( $categories ),
                            'description' => __( 'Created via tests' ),
                            'product_type' => 'product',
                            'type' => Product::TYPE_GROUPED,
                            'sku' => Str::random( 15 ) . '-sku',
                            'status' => 'available',
                            'stock_management' => 'enabled',
                        ],
                        'groups' => [
                            'product_subitems' => $products,
                        ],
                        'images' => [],
                        'taxes' => [
                            'tax_group_id' => 1,
                            'tax_type' => $taxType,
                        ],
                        'units' => [
                            'selling_group' => $unitGroup->units->map( function ( $unit ) use ( $faker, $sale_price ) {
                                return [
                                    'sale_price_edit' => $sale_price,
                                    'wholesale_price_edit' => $faker->numberBetween( 20, 25 ),
                                    'unit_id' => $unit->id,
                                ];
                            } ),
                            'unit_group' => $unitGroup->id,
                        ],
                    ],
                ],
            ] );

        $result = json_decode( $response->getContent(), true );
        $taxGroup = TaxGroup::find( 1 );

        if ( $taxType === 'exclusive' ) {
            $this->assertEquals( (float) data_get( $result, 'data.product.unit_quantities.0.sale_price' ), $taxService->getPriceWithTaxUsingGroup( $taxType, $taxGroup, $sale_price ) );
            $this->assertEquals( (float) data_get( $result, 'data.product.unit_quantities.0.sale_price_with_tax' ), $taxService->getPriceWithTaxUsingGroup( $taxType, $taxGroup, $sale_price ) );
            $this->assertEquals( (float) data_get( $result, 'data.product.unit_quantities.0.sale_price_without_tax' ), $taxService->getPriceWithoutTaxUsingGroup( $taxType, $taxGroup, $sale_price ) );
        } else {
            $this->assertEquals( (float) data_get( $result, 'data.product.unit_quantities.0.sale_price', 0 ), $taxService->getPriceWithTaxUsingGroup( $taxType, $taxGroup, $sale_price ) );
            $this->assertEquals( (float) data_get( $result, 'data.product.unit_quantities.0.sale_price_with_tax', 0 ), $taxService->getPriceWithTaxUsingGroup( $taxType, $taxGroup, $sale_price ) );
            $this->assertEquals( (float) data_get( $result, 'data.product.unit_quantities.0.sale_price_without_tax', 0 ), $taxService->getPriceWithoutTaxUsingGroup( $taxType, $taxGroup, $sale_price ) );
        }

        /**
         * We'll test if the subitems were correctly stored.
         */
        $product = Product::find( $result[ 'data' ][ 'product' ][ 'id' ] );

        $this->assertTrue( count( $products ) === $product->sub_items->count(), 'Sub items aren\'t matching' );

        $matched = $product->sub_items->filter( function ( $subItem ) use ( $products ) {
            return collect( $products )->filter( function ( $_product ) use ( $subItem ) {
                $argument = (
                    (int) $_product[ 'unit_id' ] === (int) $subItem->unit_id &&
                    (int) $_product[ 'product_id' ] === (int) $subItem->product_id &&
                    (int) $_product[ 'unit_quantity_id' ] === (int) $subItem->unit_quantity_id &&
                    (float) $_product[ 'sale_price' ] === (float) $subItem->sale_price &&
                    (float) $_product[ 'quantity' ] === (float) $subItem->quantity
                );

                return $argument;
            } )->isNotEmpty();
        } );

        $this->assertTrue( $matched->count() === count( $products ), 'Sub items accuracy failed' );

        $response->assertStatus( 200 );
    }

    protected function attemptAdjustmentByDeletion()
    {
        $productUnitQuantity = ProductUnitQuantity::where( 'quantity', '>', 10 )
            ->with( 'product' )
            ->whereRelation( 'product', function ( $query ) {
                return $query->where( 'stock_management', Product::STOCK_MANAGEMENT_ENABLED );
            } )
            ->first();

        $response = $this->json( 'POST', '/api/products/adjustments', [
            'products' => [
                [
                    'id' => $productUnitQuantity->product->id,
                    'adjust_action' => 'deleted',
                    'name' => $productUnitQuantity->product->name,
                    'adjust_unit' => $productUnitQuantity,
                    'adjust_reason' => __( 'Performing a test adjustment' ),
                    'adjust_quantity' => 1,
                ],
            ],
        ] );

        $response->assertJsonPath( 'status', 'success' );
    }

    protected function attemptTestSearchable()
    {
        $searchable = Product::searchable()->first();

        if ( $searchable instanceof Product ) {
            $response = $this
                ->withSession( $this->app[ 'session' ]->all() )
                ->json( 'GET', '/api/categories/pos/' . $searchable->category_id );

            $response = json_decode( $response->getContent(), true );
            $exists = collect( $response[ 'products' ] )
                ->filter( fn( $product ) => (int) $product[ 'id' ] === (int) $searchable->id )
                ->count() > 0;

            return $this->assertTrue( $exists, __( 'Searchable product cannot be found on category.' ) );
        }

        return $this->assertTrue( true );
    }

    protected function attemptNotSearchableAreSearchable()
    {
        $searchable = Product::searchable( false )->first();

        if ( $searchable instanceof Product ) {
            $response = $this
                ->withSession( $this->app[ 'session' ]->all() )
                ->json( 'GET', '/api/categories/pos/' . $searchable->category_id );

            $response = json_decode( $response->getContent(), true );
            $exists = collect( $response[ 'products' ] )
                ->filter( fn( $product ) => (int) $product[ 'id' ] === (int) $searchable->id )
                ->count() === 0;

            return $this->assertTrue( $exists, __( 'Not searchable product cannot be found on category.' ) );
        }

        return $this->assertTrue( true );
    }

    protected function attemptDecreaseStockCount()
    {
        $productQuantity = ProductUnitQuantity::where( 'quantity', '>', 0 )->first();

        if ( ! $productQuantity instanceof ProductUnitQuantity ) {
            throw new Exception( __( 'Unable to find a product to perform this test.' ) );
        }

        $product = $productQuantity->product;

        foreach ( ProductHistory::STOCK_REDUCE as $action ) {
            $response = $this->withSession( $this->app[ 'session' ]->all() )
                ->json( 'POST', 'api/products/adjustments', [
                    'products' => [
                        [
                            'adjust_action' => $action,
                            'adjust_unit' => [
                                'sale_price' => $productQuantity->sale_price,
                                'unit_id' => $productQuantity->unit_id,
                            ],
                            'id' => $product->id,
                            'adjust_quantity' => 1,
                        ],
                    ],
                ] );

            $oldQuantity = $productQuantity->quantity;
            $productQuantity->refresh();

            $response->assertStatus( 200 );

            $this->assertTrue(
                $oldQuantity - $productQuantity->quantity === (float) 1,
                sprintf(
                    __( 'The stock modification : %s hasn\'t made any change' ),
                    $action
                )
            );
        }
    }

    protected function attemptGroupedProductStockAdjustment()
    {
        $productQuantity = ProductUnitQuantity::whereHas( 'product', function ( $query ) {
            $query->grouped();
        } )
            ->first();

        if ( ! $productQuantity instanceof ProductUnitQuantity ) {
            throw new Exception( __( 'Unable to find a grouped product to perform this test.' ) );
        }

        $product = $productQuantity->product;

        $response = $this->withSession( $this->app['session']->all() )
            ->json( 'POST', 'api/products/adjustments', [
                'products' => [
                    [
                        'name' => $product->name,
                        'adjust_action' => ProductHistory::ACTION_ADDED,
                        'adjust_unit' => [
                            'sale_price' => $productQuantity->sale_price,
                            'unit_id' => $productQuantity->unit_id,
                        ],
                        'id' => $product->id,
                        'adjust_quantity' => 1,
                    ],
                ],
            ] );

        $response->assertStatus( 500 );
        $response->assertSeeText( 'Adjusting grouped product inventory must result of a create, update, delete sale operation.' );
    }

    protected function attemptProductStockAdjustment()
    {
        $productQuantity = ProductUnitQuantity::where( 'quantity', '>', 0 )
            ->whereHas( 'product', function ( $query ) {
                $query->notGrouped()
                    ->withStockEnabled();
            } )
            ->first();

        if ( ! $productQuantity instanceof ProductUnitQuantity ) {
            throw new Exception( __( 'Unable to find a product to perform this test.' ) );
        }

        $product = $productQuantity->product;

        foreach ( ProductHistory::STOCK_INCREASE as $action ) {
            $response = $this->withSession( $this->app[ 'session' ]->all() )
                ->json( 'POST', 'api/products/adjustments', [
                    'products' => [
                        [
                            'adjust_action' => $action,
                            'adjust_unit' => [
                                'sale_price' => $productQuantity->sale_price,
                                'unit_id' => $productQuantity->unit_id,
                            ],
                            'id' => $product->id,
                            'adjust_quantity' => 10,
                        ],
                    ],
                ] );

            $oldQuantity = $productQuantity->quantity;
            $productQuantity->refresh();

            $response->assertStatus( 200 );

            $this->assertTrue(
                $productQuantity->quantity - $oldQuantity === (float) 10,
                sprintf(
                    __( 'The stock modification : %s hasn\'t made any change' ),
                    $action
                )
            );
        }
    }

    protected function attemptSetStockCount()
    {
        $productQuantity = ProductUnitQuantity::where( 'quantity', '>', 0 )->first();

        if ( ! $productQuantity instanceof ProductUnitQuantity ) {
            throw new Exception( __( 'Unable to find a product to perform this test.' ) );
        }

        $product = $productQuantity->product;

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/products/adjustments', [
                'products' => [
                    [
                        'adjust_action' => 'set',
                        'adjust_unit' => [
                            'sale_price' => $productQuantity->sale_price,
                            'unit_id' => $productQuantity->unit_id,
                        ],
                        'id' => $product->id,
                        'adjust_quantity' => 10,
                    ],
                ],
            ] );

        $oldQuantity = $productQuantity->quantity;
        $productQuantity->refresh();

        $response->assertStatus( 200 );

        $this->assertTrue(
            $productQuantity->quantity === (float) 10,
            sprintf(
                __( 'The stock modification : %s hasn\'t made any change' ),
                'set'
            )
        );

        $this->assertNotEquals( $oldQuantity, $productQuantity->quantity );
    }

    protected function attemptProductConversion()
    {
        $product = Product::where( 'type', Product::TYPE_MATERIALIZED )
            ->has( 'unit_quantities', '>=', 2 )
            ->with( 'unit_quantities.unit' )
            ->first();

        $firstUnitQuantity = $product->unit_quantities->first();
        $secondUnitQuantity = $product->unit_quantities->last();

        /**
         * We'll provide some quantity to ensure
         * it doesn't fails because of the missing quantity.
         */
        $firstUnitQuantity->quantity = 1000;
        $firstUnitQuantity->save();

        /**
         * We'll create a conversion that should fail
         * because it will cause a float value
         */
        $response = $this->performConversionRequest( $product, [
            'from' => $firstUnitQuantity->unit->id,
            'to' => $secondUnitQuantity->unit->id,
            'quantity' => 1,
        ] );

        $response->assertStatus( 403 );

        /**
         * We'll create a conversion that should pass
         */
        $response = $this->performConversionRequest( $product, [
            'from' => $firstUnitQuantity->unit->id,
            'to' => $secondUnitQuantity->unit->id,
            'quantity' => $secondUnitQuantity->unit->value,
        ] );

        $response->assertStatus( 200 );

        $refreshedSecondUnitQuantity = $secondUnitQuantity->fresh();

        $this->assertSame( $secondUnitQuantity->quantity + 1, $refreshedSecondUnitQuantity->quantity );
    }

    private function performConversionRequest( Product $product, $data )
    {
        return $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/products/' . $product->id . '/units/conversion', $data );
    }
}
