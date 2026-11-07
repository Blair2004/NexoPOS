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

    public function test_minimal_credit_payment_must_be_a_percentage(): void
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

    public function test_minimal_credit_payment_cannot_be_negative(): void
    {
        $this->attemptAuthenticate();

        $groupCount = CustomerGroup::count();

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/crud/ns.customers-groups', [
                'name' => 'Negative Credit Group',
                'general' => [
                    'minimal_credit_payment' => -1,
                ],
            ] );

        $response->assertStatus( 422 )
            ->assertJsonValidationErrors( 'general.minimal_credit_payment' );

        $this->assertSame( $groupCount, CustomerGroup::count() );
    }

    public function test_flattened_minimal_credit_payment_is_rejected(): void
    {
        $this->attemptAuthenticate();

        $groupCount = CustomerGroup::count();
        $invalidValues = [
            'over the limit' => 12000000,
            'negative' => -1,
        ];

        foreach ( $invalidValues as $label => $value ) {
            $response = $this->withSession( $this->app[ 'session' ]->all() )
                ->json( 'POST', 'api/crud/ns.customers-groups', [
                    'name' => 'Flattened Credit Group ' . $label . ' ' . uniqid(),
                    'minimal_credit_payment' => $value,
                    'general' => [
                        'minimal_credit_payment' => 0,
                    ],
                ] );

            $response->assertForbidden()->assertJsonPath( 'status', 'error' );
        }

        $this->assertSame( $groupCount, CustomerGroup::count() );
    }

    public function test_flattened_minimal_credit_payment_is_rejected_on_update(): void
    {
        $this->attemptAuthenticate();

        $group = $this->attemptCreateCustomerGroup();
        $originalCreditPayment = $group->minimal_credit_payment;

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'PUT', 'api/crud/ns.customers-groups/' . $group->id, [
                'name' => $group->name,
                'minimal_credit_payment' => 12000000,
                'general' => [
                    'minimal_credit_payment' => 0,
                ],
            ] );

        $response->assertForbidden()->assertJsonPath( 'status', 'error' );

        $this->assertEquals( $originalCreditPayment, $group->fresh()->minimal_credit_payment );
    }
}
