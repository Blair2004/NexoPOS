<?php

namespace Tests\Feature;

use App\Models\Role;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Traits\WithCombinedProductTest;

class CombiningProductsTest extends TestCase
{
    use WithCombinedProductTest;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_combined_products()
    {
        Sanctum::actingAs(
            Role::namespace( 'admin' )->users->first(),
            ['*']
        );

        $this->attemptCombineProducts();
    }
}
