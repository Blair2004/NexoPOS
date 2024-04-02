<?php

namespace Tests\Traits;

use App\Models\Product;
use App\Models\ProductCategory;
use Exception;

trait WithCategoryTest
{
    /**
     * this requires the products to be availlable
     */
    protected function attemptDeleteCategory()
    {
        $product = Product::first();

        if ( $product instanceof Product ) {
            $response = $this->withSession( $this->app[ 'session' ]->all() )
                ->json( 'DELETE', 'api/crud/ns.products-categories/' . $product->category_id );

            return $response->assertJson( [
                'status' => 'error',
            ] );
        }

        throw new Exception( __( 'No product was found to perform the test.' ) );
    }

    protected function attemptDeleteSingleCategory( $category )
    {
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'DELETE', 'api/crud/ns.products-categories/' . $category->id );

        return $response->assertJson( [
            'status' => 'success',
        ] );
    }

    protected function attemptCreateSingleCategory()
    {
        // import faker and create a fake category name
        $faker = \Faker\Factory::create();
        $categoryName = $faker->name;

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/crud/ns.products-categories', [
                'name' => $categoryName,
                'general' => [
                    'displays_on_pos' => true,
                ],
            ] );

        $response->assertJson( [
            'status' => 'success',
        ] );

        return ProductCategory::find( $response->json()[ 'data' ][ 'entry' ][ 'id' ] );
    }

    protected function attemptUpdateCategory( ProductCategory $category )
    {
        // import faker and create a fake category name
        $faker = \Faker\Factory::create();
        $categoryName = $faker->name;

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'PUT', 'api/crud/ns.products-categories/' . $category->id, [
                'name' => $categoryName,
                'general' => [
                    'displays_on_pos' => true,
                ],
            ] );

        $response->assertJson( [
            'status' => 'success',
        ] );

        return ProductCategory::find( $response->json()[ 'data' ][ 'entry' ][ 'id' ] );
    }

    protected function attemptCreateCategory()
    {
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/crud/ns.products-categories', [
                'name' => __( 'Smartphones' ),
                'general' => [
                    'displays_on_pos' => true,
                ],
            ] );

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/crud/ns.products-categories', [
                'name' => __( 'Phones' ),
                'general' => [
                    'displays_on_pos' => true,
                ],
            ] );

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/crud/ns.products-categories', [
                'name' => __( 'Computers' ),
                'general' => [
                    'displays_on_pos' => true,
                ],
            ] );

        $response->assertJson( [
            'status' => 'success',
        ] );

        $category = ProductCategory::first();

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/crud/ns.products-categories', [
                'name' => __( 'Laptops' ),
                'general' => [
                    'parent_id' => $category->id,
                    'displays_on_pos' => true,
                ],
            ] );

        $response->assertJson( [
            'status' => 'success',
        ] );

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/crud/ns.products-categories', [
                'name' => __( 'Desktop' ),
                'general' => [
                    'parent_id' => $category->id,
                    'displays_on_pos' => true,
                ],
            ] );

        $response->assertJson( [
            'status' => 'success',
        ] );
    }
}
