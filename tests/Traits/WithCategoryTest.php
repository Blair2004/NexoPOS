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
                ->json( 'DELETE', 'api/nexopos/v4/crud/ns.products-categories/' . $product->category_id );

            return $response->assertJson([
                'status' => 'failed',
            ]);
        }

        throw new Exception( __( 'No product was found to perform the test.' ) );
    }

    protected function attemptCreateCategory()
    {
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/crud/ns.products-categories', [
                'name' => __( 'Computers' ),
                'general' => [
                    'displays_on_pos' => true,
                ],
            ]);

        $response->assertJson([
            'status' => 'success',
        ]);

        $category = ProductCategory::first();

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/crud/ns.products-categories', [
                'name' => __( 'Laptops' ),
                'general' => [
                    'parent_id' => $category->id,
                    'displays_on_pos' => true,
                ],
            ]);

        $response->assertJson([
            'status' => 'success',
        ]);

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/crud/ns.products-categories', [
                'name' => __( 'Desktop' ),
                'general' => [
                    'parent_id' => $category->id,
                    'displays_on_pos' => true,
                ],
            ]);

        $response->assertJson([
            'status' => 'success',
        ]);
    }
}
