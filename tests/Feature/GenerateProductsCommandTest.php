<?php

namespace Tests\Feature;

use App\Models\ProductCategory;
use App\Models\Unit;
use App\Models\UnitGroup;
use App\Models\User;
use App\Services\ProductGeneratorService;
use App\Services\ProductService;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

class GenerateProductsCommandTest extends TestCase
{
    public function test_it_rejects_an_invalid_product_count(): void
    {
        $this->artisan( 'ns:products:generate', [ '--count' => 0 ] )
            ->expectsOutputToContain( 'The count must be an integer between 1 and 100,000.' )
            ->assertFailed();
    }

    public function test_generator_creates_complete_varied_product_payloads(): void
    {
        $createdProducts = [];
        $productService = Mockery::mock( ProductService::class );
        $productService->shouldReceive( 'create' )
            ->times( 3 )
            ->andReturnUsing( function ( array $product ) use ( &$createdProducts ): array {
                $createdProducts[] = $product;

                return [ 'status' => 'success' ];
            } );

        $firstCategory = ( new ProductCategory )->forceFill( [ 'id' => 10, 'name' => 'Home' ] );
        $secondCategory = ( new ProductCategory )->forceFill( [ 'id' => 20, 'name' => 'Office' ] );
        $author = ( new User )->forceFill( [ 'id' => 30 ] );
        $unitGroup = ( new UnitGroup )->forceFill( [ 'id' => 40 ] );
        $unitGroup->setRelation( 'units', new Collection( [
            ( new Unit )->forceFill( [ 'id' => 50, 'group_id' => 40, 'value' => 1 ] ),
            ( new Unit )->forceFill( [ 'id' => 60, 'group_id' => 40, 'value' => 2 ] ),
        ] ) );
        $progress = [];

        $generated = ( new ProductGeneratorService( $productService ) )->generate(
            count: 3,
            categories: new Collection( [ $firstCategory, $secondCategory ] ),
            unitGroup: $unitGroup,
            author: $author,
            afterProductCreated: function ( int $index ) use ( &$progress ): void {
                $progress[] = $index;
            },
        );

        $this->assertSame( 3, $generated );
        $this->assertSame( [ 1, 2, 3 ], $progress );
        $this->assertSame( [ 10, 20, 10 ], array_column( $createdProducts, 'category_id' ) );
        $this->assertCount( 3, array_unique( array_column( $createdProducts, 'name' ) ) );
        $this->assertCount( 3, array_unique( array_column( $createdProducts, 'sku' ) ) );
        $this->assertCount( 3, array_unique( array_column( $createdProducts, 'barcode' ) ) );

        foreach ( $createdProducts as $product ) {
            $this->assertSame( 'disabled', $product[ 'stock_management' ] );
            $this->assertSame( 30, $product[ 'author_id' ] );
            $this->assertSame( 40, $product[ 'units' ][ 'unit_group' ] );
            $this->assertCount( 2, $product[ 'units' ][ 'selling_group' ] );
            $this->assertTrue( $product[ 'images' ][0][ 'featured' ] );
            $this->assertStringContainsString( '/images/products/', $product[ 'images' ][0][ 'url' ] );
            $this->assertGreaterThan(
                $product[ 'units' ][ 'selling_group' ][0][ 'wholesale_price_edit' ],
                $product[ 'units' ][ 'selling_group' ][0][ 'sale_price_edit' ],
            );
        }

        $this->assertNotSame(
            $createdProducts[0][ 'images' ][0][ 'url' ],
            $createdProducts[1][ 'images' ][0][ 'url' ],
        );
    }
}
