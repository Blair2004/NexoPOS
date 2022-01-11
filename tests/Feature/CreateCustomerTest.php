<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerAccountHistory;
use App\Models\CustomerGroup;
use App\Models\Role;
use App\Services\CustomerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Faker\Factory;
use Illuminate\Support\Facades\Auth;

class CreateCustomerTest extends TestCase
{
    use WithFaker;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testExample()
    {
        Sanctum::actingAs(
            Role::namespace( 'admin' )->users->first(),
            ['*']
        );

        $faker              =   Factory::create();

        /**
         * @var CustomerService $customerService
         */
        $customerService    =   app()->make( CustomerService::class );
        $group              =   CustomerGroup::first();

        for( $i = 0 ; $i < 10; $i++ ) {
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
            $customerAccountHistory     =   new CustomerAccountHistory();
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
