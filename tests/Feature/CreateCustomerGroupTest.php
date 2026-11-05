<?php

namespace Tests\Feature;

use App\Models\CustomerGroup;
use Tests\TestCase;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithCustomerTest;

class CreateCustomerGroupTest extends TestCase
{
    use WithAuthentication, WithCustomerTest;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_create_customer_group()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateCustomerGroup();
    }

    public function test_minimal_credit_payment_must_be_a_percentage()
    {
        $this->attemptAuthenticate();

        $groupCount = CustomerGroup::count();

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/crud/ns.customers-groups', [
                'name' => 'Invalid Credit Group',
                'general' => [
                    'minimal_credit_payment' => 12000000,
                ],
            ] );

        $response->assertStatus( 422 )
            ->assertJsonValidationErrors( 'general.minimal_credit_payment' );

        $this->assertSame( $groupCount, CustomerGroup::count() );
    }
}
