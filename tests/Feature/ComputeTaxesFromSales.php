<?php

namespace Tests\Feature;

use App\Models\Role;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Traits\WithTaxTest;

class ComputeTaxesFromSales extends TestCase
{
    use WithTaxTest;

    public function test_product_tax_variable()
    {
        Sanctum::actingAs(
            Role::namespace( 'admin' )->users->first(),
            ['*']
        );

        $this->attemptProductTaxVariable();
    }

    public function test_tax_products_vat()
    {
        Sanctum::actingAs(
            Role::namespace( 'admin' )->users->first(),
            ['*']
        );

        $this->attemptTaxProductVat();
    }

    public function test_variable_vat()
    {
        Sanctum::actingAs(
            Role::namespace( 'admin' )->users->first(),
            ['*']
        );

        $this->attemptVariableVat();
    }

    public function test_flat_expense()
    {
        Sanctum::actingAs(
            Role::namespace( 'admin' )->users->first(),
            ['*']
        );

        $this->attemptFlatExpense();
    }

    /**
     * Check the tax calculation
     *
     * @return void
     */
    public function test_inclusive_tax()
    {
        Sanctum::actingAs(
            Role::namespace( 'admin' )->users->first(),
            ['*']
        );

        $this->attemptInclusiveTax();
    }

    /**
     * Check the tax calculation
     *
     * @return void
     */
    public function test_exclusive_tax()
    {
        Sanctum::actingAs(
            Role::namespace( 'admin' )->users->first(),
            ['*']
        );

        $this->attemptExclusiveTax();
    }
}
