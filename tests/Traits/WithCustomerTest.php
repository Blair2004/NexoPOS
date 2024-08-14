<?php

namespace Tests\Traits;

use App\Models\Coupon;
use App\Models\Customer;
use App\Models\CustomerAccountHistory;
use App\Models\CustomerGroup;
use App\Models\RewardSystem;
use App\Services\CustomerService;
use Exception;
use Faker\Factory;
use Illuminate\Foundation\Testing\WithFaker;

trait WithCustomerTest
{
    use WithFaker;

    protected function attemptCreateCustomerGroup()
    {
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/crud/ns.customers-groups', [
                'name' => __( 'Base Customers' ),
                'general' => [
                    'reward_system_id' => $this->faker->randomElement( RewardSystem::get()->map( fn( $reward ) => $reward->id )->toArray() ),
                ],
            ] );

        $response->assertJson( [
            'status' => 'success',
        ] );

        return CustomerGroup::findOrFail( $response->json()[ 'data' ][ 'entry' ][ 'id' ] );
    }

    protected function attemptRemoveCreditCustomerAccount()
    {
        $customer = Customer::where( 'account_amount', 0 )
            ->first();

        if ( $customer instanceof Customer ) {
            $response = $this
                ->withSession( $this->app[ 'session' ]->all() )
                ->json( 'POST', '/api/customers/' . $customer->id . '/account-history', [
                    'amount' => 500,
                    'description' => __( 'Test credit account' ),
                    'operation' => CustomerAccountHistory::OPERATION_DEDUCT,
                ] );

            return $response->assertJson( [ 'status' => 'error' ] );
        }

        throw new Exception( __( 'No customer with empty account to proceed the test.' ) );
    }

    protected function attemptCreditCustomerAccount()
    {
        $customer = Customer::where( 'account_amount', 0 )
            ->first();

        if ( $customer instanceof Customer ) {
            $response = $this
                ->withSession( $this->app[ 'session' ]->all() )
                ->json( 'POST', '/api/customers/' . $customer->id . '/account-history', [
                    'amount' => 500,
                    'description' => __( 'Test credit account' ),
                    'operation' => CustomerAccountHistory::OPERATION_ADD,
                ] );

            return $response->assertJson( [ 'status' => 'success' ] );
        }

        throw new Exception( __( 'No customer with empty account to proceed the test.' ) );
    }

    protected function attemptCreateCustomerWithNoEmail()
    {
        $faker = Factory::create();
        $group = CustomerGroup::first();

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/crud/ns.customers', [
                'first_name' => $faker->firstName,
                'general' => [
                    'group_id' => $group->id,
                    'last_name' => $faker->lastName,
                ],
                'shipping' => [
                    'first_name' => $faker->firstName,
                    'email' => $faker->email,
                ],
            ] );

        $this->attemptTestCustomerGroup( Customer::find( $response->json()[ 'data' ][ 'entry' ][ 'id' ] ) );

        $response->assertJson( [
            'status' => 'success',
        ] );
    }

    protected function attemptCreateCustomersWithSimilarEmail()
    {
        ns()->option->set( 'ns_customers_force_valid_email', 'yes' );

        $faker = Factory::create();
        $group = CustomerGroup::first();
        $email = $faker->email;

        /**
         * The first attempt should
         * be successful.
         */
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/crud/ns.customers', [
                'first_name' => $faker->firstName,
                'general' => [
                    'group_id' => $group->id,
                    'last_name' => $faker->lastName,
                    'email' => $email,
                ],
                'shipping' => [
                    'first_name' => $faker->firstName,
                    'email' => $faker->email,
                ],
            ] );

        $response->assertJson( [
            'status' => 'success',
        ] );

        $customer = Customer::find( $response->json()[ 'data' ][ 'entry' ][ 'id' ] );

        $this->attemptTestCustomerGroup( $customer );

        /**
         * The second should fail as we're
         * using the exact same non-empty email
         */
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/crud/ns.customers', [
                'first_name' => $faker->firstName,
                'general' => [
                    'group_id' => $group->id,
                    'last_name' => $faker->lastName,
                    'email' => $email,
                ],
                'shipping' => [
                    'first_name' => $faker->firstName,
                    'email' => $faker->email,
                ],
            ] );

        $response->assertJson( [
            'status' => 'error',
        ] );
    }

    public function attemptTestCustomerGroup( Customer $customer )
    {
        $this->assertTrue( $customer->group instanceof CustomerGroup );
    }

    protected function attemptCreateCustomer()
    {
        $faker = Factory::create();
        $group = CustomerGroup::first();

        /**
         * Creating a first customer
         */
        $email = $faker->email;
        $firstName = $faker->firstName;
        $lastName = $faker->lastName;

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/crud/ns.customers', [
                'first_name' => $firstName,
                'general' => [
                    'group_id' => $group->id,
                    'last_name' => $faker->lastName,
                    'email' => $email,
                ],
                'shipping' => [
                    'type' => 'shipping',
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                ],
                'billing' => [
                    'type' => 'billing',
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                ],
            ] );

        $response->assertJson( [
            'status' => 'success',
        ] );

        return Customer::with( 'group' )->findOrFail( $response->json()[ 'data' ][ 'entry' ][ 'id' ] );
    }

    protected function attemptCreateCustomerWithInitialTransactions()
    {
        /**
         * @var CustomerService $customerService
         */
        $customerService = app()->make( CustomerService::class );

        $customer = $this->attemptCreateCustomer();
        $oldCustomerBalance = $customer->account_amount;

        $this->attemptTestCustomerGroup( $customer );

        /**
         * For each customer
         * let's create a crediting operation
         */
        if ( $this->faker->randomElement( [ true, false ] ) ) {
            $randomAmount = $this->faker->randomNumber( 3, true );

            /**
             * Step 1: we'll make some transaction
             * and verify how it goes.
             */
            $result = $customerService->saveTransaction(
                $customer,
                CustomerAccountHistory::OPERATION_ADD,
                $randomAmount,
                'Created from tests',
            );

            $history = $result[ 'data' ][ 'customerAccountHistory' ];

            $this->assertSame( (float) $history->amount, (float) $randomAmount, 'The amount is not refected on the history.' );
            $this->assertSame( (float) $history->next_amount, (float) $randomAmount, 'The amount is not refected on the history.' );
            $this->assertSame( (float) $history->previous_amount, (float) 0, 'The previous amount is not accurate.' );

            $customer->refresh();

            $this->assertSame( (float) $oldCustomerBalance + $randomAmount, (float) $customer->account_amount, 'The customer account hasn\'t been updated.' );

            /**
             * Step 2: second control and verification on
             * how it goes.
             */
            $result = $customerService->saveTransaction(
                $customer,
                CustomerAccountHistory::OPERATION_DEDUCT,
                $randomAmount,
                'Created from tests',
            );

            $customer->refresh();

            $history = $result[ 'data' ][ 'customerAccountHistory' ];

            $this->assertSame( (float) $history->amount, (float) $randomAmount, 'The amount is not refected on the history.' );
            $this->assertSame( (float) $history->next_amount, (float) 0, 'The amount is not refected on the history.' );
            $this->assertSame( (float) $history->previous_amount, (float) $randomAmount, 'The previous amount is not accurate.' );
        }
    }

    protected function attemptCreateReward()
    {
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'post', 'api/crud/ns.rewards-system', [
                'name' => __( 'Sample Reward System' ),
                'general' => [
                    'coupon_id' => $this->faker->randomElement( Coupon::get()->map( fn( $coupon ) => $coupon->id )->toArray() ),
                    'target' => $this->faker->randomElement( [ 10, 20, 30 ] ),
                ],
                'rules' => [
                    [
                        'from' => 0,
                        'to' => 10,
                        'reward' => 1,
                    ], [
                        'from' => 10,
                        'to' => 50,
                        'reward' => 3,
                    ], [
                        'from' => 50,
                        'to' => 100,
                        'reward' => 5,
                    ],
                ],
            ] );

        $response->assertStatus( 200 );
    }

    protected function attemptGetCustomerHistory()
    {
        $accountHistory = CustomerAccountHistory::first();

        if ( $accountHistory instanceof CustomerAccountHistory ) {
            $response = $this->withSession( $this->app[ 'session' ]->all() )
                ->json( 'GET', 'api/customers/' . $accountHistory->customer_id . '/account-history' );

            $response->assertOk();
            $response = json_decode( $response->getContent(), true );
            $result = collect( $response[ 'data' ] )->filter( fn( $entry ) => (int) $accountHistory->id === (int) $entry[ 'id' ] );

            return $this->assertTrue(
                $result->isNotEmpty(),
                'The customer history doesn\'t have recognized information'
            );
        }

        throw new Exception( 'Unable to perform the test without a valid history.' );
    }

    protected function attemptSearchCustomers()
    {
        $faker = Factory::create();
        $group = CustomerGroup::first();

        /**
         * Creating a first customer
         */
        $email = $faker->email;
        $firstName = $faker->firstName;
        $lastName = $faker->lastName;

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/crud/ns.customers', [
                'first_name' => $firstName,
                'general' => [
                    'group_id' => $group->id,
                    'last_name' => $faker->lastName,
                    'email' => $email,
                ],
                'shipping' => [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                ],
                'billing' => [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                ],
            ] );

        $response->assertJson( [
            'status' => 'success',
        ] );

        $lastCustomer = Customer::where( 'first_name', '!=', null )->orderBy( 'id', 'desc' )->first();

        /**
         * let's now search
         */
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/customers/search', [
                'search' => $lastCustomer->first_name,
            ] );

        $response->assertJsonPath( '0.id', $lastCustomer->id );
    }

    protected function attemptGetCustomerReward()
    {
        $customer = Customer::first();
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'GET', 'api/customers/' . $customer->id . '/rewards' );

        $response->assertOk();
    }

    protected function attemptGetCustomerOrders()
    {
        $customer = Customer::first();
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'GET', 'api/customers/' . $customer->id . '/orders' );

        $response->assertOk();
    }

    protected function attemptGetOrdersAddresses()
    {
        $customer = Customer::first();
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'GET', 'api/customers/' . $customer->id . '/addresses' );

        $response->assertOk();
    }

    protected function attemptGetCustomerGroup()
    {
        $customer = Customer::first();
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'GET', 'api/customers/' . $customer->id . '/group' );

        $response->assertOk();
    }

    protected function attemptDeleteCustomer( Customer $customer )
    {
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'DELETE', 'api/crud/ns.customers/' . $customer->id );

        $response->assertJson( [
            'status' => 'success',
        ] );

        return $customer;
    }

    protected function attemptUpdateCustomer( Customer $customer )
    {
        $faker = Factory::create();
        $group = $this->attemptCreateCustomerGroup();

        /**
         * Creating a first customer
         */
        $email = $faker->email;
        $firstName = $faker->firstName;
        $lastName = $faker->lastName;

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'PUT', 'api/crud/ns.customers/' . $customer->id, [
                'first_name' => $firstName,
                'general' => [
                    'group_id' => $group->id,
                    'last_name' => $faker->lastName,
                    'email' => $customer->email,
                ],
                'shipping' => [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                ],
                'billing' => [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                ],
            ] );

        $response->assertJson( [
            'status' => 'success',
        ] );

        $this->assertFalse( $customer->group->id === $group->id );

        return Customer::with( 'group' )->findOrFail( $response->json()[ 'data' ][ 'entry' ][ 'id' ] );
    }
}
