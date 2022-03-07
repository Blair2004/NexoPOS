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
use Illuminate\Support\Facades\Auth;

trait WithCustomerTest
{
    use WithFaker;
    
    protected function attemptCreateCustomerGroup()
    {
        $response       =   $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/crud/ns.customers-groups', [
                'name'  =>  __( 'Base Customers' ),
                'general'   =>  [
                    'reward_system_id'  =>  $this->faker->randomElement( RewardSystem::get()->map( fn( $reward ) => $reward->id )->toArray() )
                ]
            ]);

        $response->assertJson([
            'status'    =>  'success'
        ]);
    }

    protected function attemptRemoveCreditCustomerAccount()
    {
        $customer          =   Customer::where( 'account_amount', 0 )
            ->first();

        if ( $customer instanceof Customer ) {
            $response = $this
                ->withSession( $this->app[ 'session' ]->all() )
                ->json( 'POST', '/api/nexopos/v4/customers/' . $customer->id . '/account-history', [
                    'amount'        =>  500,
                    'description'   =>  __( 'Test credit account' ),
                    'operation'     =>  CustomerAccountHistory::OPERATION_DEDUCT
                ]);

            return $response->assertJson([ 'status' => 'failed' ]);
        }

        throw new Exception( __( 'No customer with empty account to proceed the test.' ) );
    }

    protected function attemptCreditCustomerAccount()
    {
        $customer          =   Customer::where( 'account_amount', 0 )
            ->first();

        if ( $customer instanceof Customer ) {
            $response = $this
                ->withSession( $this->app[ 'session' ]->all() )
                ->json( 'POST', '/api/nexopos/v4/customers/' . $customer->id . '/account-history', [
                    'amount'        =>  500,
                    'description'   =>  __( 'Test credit account' ),
                    'operation'     =>  CustomerAccountHistory::OPERATION_ADD
                ]);

            return $response->assertJson([ 'status'    =>  'success' ]);
        }

        throw new Exception( __( 'No customer with empty account to proceed the test.' ) );
    }

    protected function attemptCreateCustomer()
    {
        $faker              =   Factory::create();

        /**
         * @var CustomerService $customerService
         */
        $customerService    =   app()->make( CustomerService::class );
        $group              =   CustomerGroup::first();

        for( $i = 0 ; $i < 100; $i++ ) {
            /**
             * Creating a first customer
             */
            $response       =   $this->withSession( $this->app[ 'session' ]->all() )
                ->json( 'POST', 'api/nexopos/v4/crud/ns.customers', [
                    'name'  =>  $faker->firstName,
                    'general'   =>  [
                        'group_id'  =>  $group->id,
                        'surname'   =>  $faker->lastName,
                        'email'     =>  $faker->randomElement([ $faker->email, '' ])
                    ],
                    'shipping'  =>  [
                        'name'  =>  $faker->firstName,
                        'email' =>  $faker->email,
                    ]
                ]);
    
            $response->assertJson([
                'status'    =>  'success'
            ]);

            $lastCustomer   =   Customer::orderBy( 'id', 'desc' )->first();

            /**
             * For each customer
             * let's create a crediting operation
             */
            if ( $this->faker->randomElement([ true, false ]) ) {
                $customerAccountHistory                 =   new CustomerAccountHistory();
                $customerAccountHistory->customer_id    =   $lastCustomer->id;
                $customerAccountHistory->operation      =   CustomerAccountHistory::OPERATION_ADD;
                $customerAccountHistory->amount         =   $this->faker->randomNumber(3,true);
                $customerAccountHistory->author         =   Auth::id();
                $customerAccountHistory->save();
    
                $customerService->updateCustomerAccount( $customerAccountHistory );
                $lastCustomer->refresh();
    
                $this->assertTrue( $customerAccountHistory->amount == $lastCustomer->account_amount, 'The customer account hasn\'t been updated.' );
            }
        }
    }

    protected function attemptCreateReward()
    {
        $response       =   $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'post', 'api/nexopos/v4/crud/ns.rewards-system', [
                'name'          =>  __( 'Sample Reward System' ),
                'general'       =>  [
                    'coupon_id'         =>  $this->faker->randomElement( Coupon::get()->map( fn( $coupon ) => $coupon->id )->toArray() ),
                    'target'            =>  $this->faker->randomElement([ 10, 20, 30 ]),
                ],
                'rules'         =>  [
                    [
                        'from'      =>  0,
                        'to'        =>  10,
                        'reward'    =>  1
                    ], [
                        'from'      =>  10,
                        'to'        =>  50,
                        'reward'    =>  3
                    ], [
                        'from'      =>  50,
                        'to'        =>  100,
                        'reward'    =>  5
                    ]
                ]
            ]);

        $response->assertStatus(200);
    }
}