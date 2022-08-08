<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithCategoryTest;
use Tests\Traits\WithProductTest;

class CreateProductTest extends TestCase
{
    use WithProductTest, WithAuthentication, WithCategoryTest;

    /**
     * @depends testCreateProducts
     */
    public function testCreateGroupedProducts()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateGroupedProduct();
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
        $this->attemptCreateProduct();
        $this->attemptDeleteCategory();
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
}
