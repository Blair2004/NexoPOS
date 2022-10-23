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
            ->json( 'POST', 'api/nexopos/v4/crud/ns.customers-groups', [
                'name' => __( 'Base Customers' ),
                'general' => [
                    'reward_system_id' => $this->faker->randomElement( RewardSystem::get()->map( fn( $reward ) => $reward->id )->toArray() ),
                ],
            ]);

        $response->assertJson([
            'status' => 'success',
        ]);
    }

    protected function attemptRemoveCreditCustomerAccount()
    {
        $customer = Customer::where( 'account_amount', 0 )
            ->first();

        if ( $customer instanceof Customer ) {
            $response = $this
                ->withSession( $this->app[ 'session' ]->all() )
                ->json( 'POST', '/api/nexopos/v4/customers/' . $customer->id . '/account-history', [
                    'amount' => 500,
                    'description' => __( 'Test credit account' ),
                    'operation' => CustomerAccountHistory::OPERATION_DEDUCT,
                ]);

            return $response->assertJson([ 'status' => 'failed' ]);
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
                ->json( 'POST', '/api/nexopos/v4/customers/' . $customer->id . '/account-history', [
                    'amount' => 500,
                    'description' => __( 'Test credit account' ),
                    'operation' => CustomerAccountHistory::OPERATION_ADD,
                ]);

            return $response->assertJson([ 'status' => 'success' ]);
        }

        throw new Exception( __( 'No customer with empty account to proceed the test.' ) );
    }

    protected function attemptCreateCustomerWithNoEmail()
    {
        $faker = Factory::create();
        $group = CustomerGroup::first();

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/crud/ns.customers', [
                'name' => $faker->firstName,
                'general' => [
                    'group_id' => $group->id,
                    'surname' => $faker->lastName,
                ],
                'shipping' => [
                    'name' => $faker->firstName,
                    'email' => $faker->email,
                ],
            ]);

        $response->assertJson([
            'status' => 'success',
        ]);
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
            ->json( 'POST', 'api/nexopos/v4/crud/ns.customers', [
                'name' => $faker->firstName,
                'general' => [
                    'group_id' => $group->id,
                    'surname' => $faker->lastName,
                    'email' => $email,
                ],
                'shipping' => [
                    'name' => $faker->firstName,
                    'email' => $faker->email,
                ],
            ]);

        $response->assertJson([
            'status' => 'success',
        ]);

        /**
         * The second should fail as we're
         * using the exact same non-empty email
         */
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/crud/ns.customers', [
                'name' => $faker->firstName,
                'general' => [
                    'group_id' => $group->id,
                    'surname' => $faker->lastName,
                    'email' => $email,
                ],
                'shipping' => [
                    'name' => $faker->firstName,
                    'email' => $faker->email,
                ],
            ]);

        $response->assertJson([
            'status' => 'failed',
        ]);
    }

    protected function attemptCreateCustomer()
    {
        $faker = Factory::create();

        /**
         * @var CustomerService $customerService
         */
        $customerService = app()->make( CustomerService::class );
        $group = CustomerGroup::first();

        for ( $i = 0; $i < 10; $i++ ) {
            /**
             * Creating a first customer
             */
            $email = $faker->email;
            $firstName = $faker->firstName;
            $lastName = $faker->lastName;

            $response = $this->withSession( $this->app[ 'session' ]->all() )
                ->json( 'POST', 'api/nexopos/v4/crud/ns.customers', [
                    'name' => $firstName,
                    'general' => [
                        'group_id' => $group->id,
                        'surname' => $faker->lastName,
                        'email' => $email,
                    ],
                    'shipping' => [
                        'name' => $firstName,
                        'surname' => $lastName,
                        'email' => $email,
                    ],
                    'billing' => [
                        'name' => $firstName,
                        'surname' => $lastName,
                        'email' => $email,
                    ],
                ]);

            $response->assertJson([
                'status' => 'success',
            ]);

            $lastCustomer = Customer::orderBy( 'id', 'desc' )->first();

            /**
             * For each customer
             * let's create a crediting operation
             */
            if ( $this->faker->randomElement([ true, false ]) ) {
                $randomAmount = $this->faker->randomNumber(3, true);

                /**
                 * Step 1: we'll make some transaction
                 * and verify how it goes.
                 */
                $result = $customerService->saveTransaction(
                    $lastCustomer,
                    CustomerAccountHistory::OPERATION_ADD,
                    $randomAmount,
                    'Created from tests',
                );

                $history = $result[ 'data' ][ 'customerAccountHistory' ];

                $this->assertSame( (float) $history->amount, (float) $randomAmount, 'The amount is not refected on the history.' );
                $this->assertSame( (float) $history->next_amount, (float) $randomAmount, 'The amount is not refected on the history.' );
                $this->assertSame( (float) $history->previous_amount, (float) 0, 'The previous amount is not accurate.' );

                $lastCustomer->refresh();

                $this->assertSame( (float) $randomAmount, (float) $lastCustomer->account_amount, 'The customer account hasn\'t been updated.' );

                /**
                 * Step 2: second control and verification on
                 * how it goes.
                 */
                $result = $customerService->saveTransaction(
                    $lastCustomer,
                    CustomerAccountHistory::OPERATION_DEDUCT,
                    $randomAmount,
                    'Created from tests',
                );

                $lastCustomer->refresh();

                $history = $result[ 'data' ][ 'customerAccountHistory' ];

                $this->assertSame( (float) $history->amount, (float) $randomAmount, 'The amount is not refected on the history.' );
                $this->assertSame( (float) $history->next_amount, (float) 0, 'The amount is not refected on the history.' );
                $this->assertSame( (float) $history->previous_amount, (float) $randomAmount, 'The previous amount is not accurate.' );
            }
        }
    }

    protected function attemptCreateReward()
    {
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'post', 'api/nexopos/v4/crud/ns.rewards-system', [
                'name' => __( 'Sample Reward System' ),
                'general' => [
                    'coupon_id' => $this->faker->randomElement( Coupon::get()->map( fn( $coupon ) => $coupon->id )->toArray() ),
                    'target' => $this->faker->randomElement([ 10, 20, 30 ]),
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
            ]);

        $response->assertStatus(200);
    }

    protected function attemptGetCustomerHistory()
    {
        $accountHistory = CustomerAccountHistory::first();

        if ( $accountHistory instanceof CustomerAccountHistory ) {
            $response = $this->withSession( $this->app[ 'session' ]->all() )
                ->json( 'GET', 'api/nexopos/v4/customers/' . $accountHistory->customer_id . '/account-history' );

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

    protected function attemptGetCustomerReward()
    {
        $customer = Customer::first();
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'GET', 'api/nexopos/v4/customers/' . $customer->id . '/rewards' );

        $response->assertOk();
    }

    protected function attemptGetCustomerOrders()
    {
        $customer = Customer::first();
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'GET', 'api/nexopos/v4/customers/' . $customer->id . '/orders' );

        $response->assertOk();
    }

    protected function attemptGetOrdersAddresses()
    {
        $customer = Customer::first();
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'GET', 'api/nexopos/v4/customers/' . $customer->id . '/addresses' );

        $response->assertOk();
    }

    protected function attemptGetCustomerGroup()
    {
        $customer = Customer::first();
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'GET', 'api/nexopos/v4/customers/' . $customer->id . '/group' );

        $response->assertOk();
    }
}
