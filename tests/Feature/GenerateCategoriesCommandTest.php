<?php

namespace Tests\Feature;

use App\Events\ProductCategoryAfterCreatedEvent;
use App\Models\ProductCategory;
use App\Models\User;
use App\Services\ProductCategoryGeneratorService;
use App\Services\ProductCategoryService;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\TestCase;

class GenerateCategoriesCommandTest extends TestCase
{
    public function test_it_rejects_an_invalid_category_count(): void
    {
        $this->artisan( 'ns:categories:generate', [ '--count' => 0 ] )
            ->expectsOutputToContain( 'The count must be an integer between 1 and 100,000.' )
            ->assertFailed();
    }

    public function test_generator_creates_varied_categories_with_generic_images(): void
    {
        $createdCategories = [];
        $categoryService = Mockery::mock( ProductCategoryService::class );
        $categoryService->shouldReceive( 'create' )
            ->times( 3 )
            ->andReturnUsing( function ( array $category ) use ( &$createdCategories ): array {
                $createdCategories[] = $category;

                return [ 'status' => 'success' ];
            } );

        $parent = ( new ProductCategory )->forceFill( [ 'id' => 25, 'name' => 'Parent' ] );
        $author = ( new User )->forceFill( [ 'id' => 30 ] );
        $progress = [];

        $generated = ( new ProductCategoryGeneratorService( $categoryService ) )->generate(
            count: 3,
            author: $author,
            parent: $parent,
            afterCategoryCreated: function ( int $index ) use ( &$progress ): void {
                $progress[] = $index;
            },
        );

        $this->assertSame( 3, $generated );
        $this->assertSame( [ 1, 2, 3 ], $progress );
        $this->assertCount( 3, array_unique( array_column( $createdCategories, 'name' ) ) );

        foreach ( $createdCategories as $category ) {
            $this->assertSame( 25, $category[ 'parent_id' ] );
            $this->assertSame( 30, $category[ 'author_id' ] );
            $this->assertTrue( $category[ 'displays_on_pos' ] );
            $this->assertStringContainsString( '/images/', $category[ 'preview_url' ] );
            $this->assertSame( 'Generated sample product category.', $category[ 'description' ] );
        }

        $this->assertNotSame(
            $createdCategories[0][ 'preview_url' ],
            $createdCategories[1][ 'preview_url' ],
        );
    }

    public function test_category_service_accepts_an_explicit_author_for_console_creation(): void
    {
        Event::fake( [ ProductCategoryAfterCreatedEvent::class ] );

        $category = Mockery::mock( ProductCategory::class )->makePartial();
        $category->shouldReceive( 'save' )->once()->andReturnTrue();

        ( new ProductCategoryService )->create( [
            'name' => 'Generated Category',
            'author_id' => 42,
        ], $category );

        $this->assertSame( 42, $category->author_id );
        Event::assertDispatched( ProductCategoryAfterCreatedEvent::class );
    }
}
