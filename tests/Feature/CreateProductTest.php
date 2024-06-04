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
     * @depends testCreateProducts
     */
    public function testCreateGroupedProducts()
    {
        $this->attemptAuthenticate();

        for ( $i = 0; $i < 2; $i++ ) {
            $this->attemptCreateGroupedProduct();
        }
    }

    /**
     * @depends testCreateGroupedProducts
     */
    public function testDeleteProducts()
    {
        $this->attemptAuthenticate();
        $this->attemptDeleteProducts();
    }

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCreateProducts()
    {
        $this->attemptAuthenticate();

        for ( $i = 0; $i <= 5; $i++ ) {
            $this->attemptSetProduct();
        }

        $this->attemptDeleteCategory();
    }

    public function testEditProductByChangingCategory()
    {
        $this->attemptAuthenticate();
        $this->attemptChangeProductCategory();
    }

    public function testSearchableAreSearchable()
    {
        $this->attemptAuthenticate();

        return $this->attemptTestSearchable();
    }

    public function testNotSearchableAreSearchable()
    {
        $this->attemptAuthenticate();
        $this->attemptNotSearchableAreSearchable();
    }

    public function testProductConversion()
    {
        $this->attemptAuthenticate();
        $this->attemptProductConversion();
    }
}
