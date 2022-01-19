<?php

namespace Tests\Feature;

use App\Models\Role;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithProductTest;

class CreateProductTest extends TestCase
{
    use WithProductTest, WithAuthentication;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCreateProducts()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateProduct();
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
