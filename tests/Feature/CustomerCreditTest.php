<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerAccountHistory;
use App\Models\Role;
use App\Models\User;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerAddCreditTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testAddCredit()
    {
        Sanctum::actingAs(
            Role::namespace( 'admin' )->users->first(),
            ['*']
        );

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

    public function testRemoveCredit()
    {
        Sanctum::actingAs(
            Role::namespace( 'admin' )->users->first(),
            ['*']
        );

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
}
