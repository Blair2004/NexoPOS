<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithCategoryTest;
use Tests\Traits\WithProductTest;

class CreateProductTest extends TestCase
{
    use WithAuthentication, WithCategoryTest, WithProductTest;

    /**
     * @depends test_create_products
     */
    public function test_create_grouped_products()
    {
        $this->attemptAuthenticate();

        for ( $i = 0; $i < 2; $i++ ) {
            $this->attemptCreateGroupedProduct();
        }
    }

    /**
     * @depends test_create_grouped_products
     */
    public function test_delete_products()
    {
        $this->attemptAuthenticate();
        $this->attemptDeleteProducts();
    }

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_create_products()
    {
        $this->attemptAuthenticate();

        for ( $i = 0; $i <= 5; $i++ ) {
            $this->attemptSetProduct();
        }

        $this->attemptDeleteCategory();
    }

    public function test_edit_product_by_changing_category()
    {
        $this->attemptAuthenticate();
        $this->attemptChangeProductCategory();
    }

    public function test_searchable_are_searchable()
    {
        $this->attemptAuthenticate();

        return $this->attemptTestSearchable();
    }

    public function test_not_searchable_are_searchable()
    {
        $this->attemptAuthenticate();
        $this->attemptNotSearchableAreSearchable();
    }

    public function test_product_conversion()
    {
        $this->attemptAuthenticate();
        $this->attemptProductConversion();
    }
}
