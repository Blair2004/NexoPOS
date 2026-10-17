<?php

namespace Tests\Feature;

use App\Crud\ProductCategoryCrud;
use App\Models\ProductCategory;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\WithAuthentication;

class CategorySearchTest extends TestCase
{
    use WithAuthentication;

    public function test_category_search_returns_a_limited_result_and_remaining_count(): void
    {
        $this->attemptAuthenticate();

        $prefix = 'Remote Category ' . Str::upper( Str::random( 8 ) );
        $categories = collect( range( 1, 3 ) )->map( fn( $index ) => ProductCategory::factory()->create( [
            'name' => $prefix . ' ' . $index,
        ] ) );

        $response = $this->getJson( '/api/crud/ns.products-categories?' . http_build_query( [
            'search' => $prefix,
            'per_page' => 2,
            'page' => 1,
        ] ) );

        $response->assertOk()
            ->assertJsonCount( 2, 'data' )
            ->assertJsonPath( 'total', 3 );

        $selectedResponse = $this->getJson( '/api/crud/ns.products-categories/' . $categories->last()->id );

        $selectedResponse->assertOk()->assertJsonFragment( [
            'name' => $categories->last()->name,
            'id' => $categories->last()->id,
        ] );
    }

    public function test_product_form_uses_crud_category_search_configuration(): void
    {
        $this->attemptAuthenticate();

        $response = $this->getJson( '/api/crud/ns.products/form-config' );

        $response->assertOk();

        $categoryField = collect( $response->json( 'form.variations.0.tabs.identification.fields' ) )
            ->firstWhere( 'name', 'category_id' );

        $this->assertIsArray( $categoryField );
        $this->assertArrayHasKey( 'search', $categoryField['options'] );
        $this->assertSame( ProductCategoryCrud::IDENTIFIER, $categoryField['options']['search']['identifier'] );
        $this->assertSame( 10, $categoryField['options']['search']['limit'] );
    }
}
