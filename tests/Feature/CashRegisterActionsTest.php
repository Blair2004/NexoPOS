<?php

namespace Tests\Feature;

use App\Models\Register;
use App\Models\RegisterHistory;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CashRegisterActionsTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_example()
    {
        Sanctum::actingAs(
            Role::namespace( 'admin' )->users->first(),
            ['*']
        );

        $response       =   $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/crud/ns.registers', [
                'name'                  =>  __( 'Test Cash Register' ),
                'general'               =>  [
                    'status'            =>  Register::STATUS_CLOSED
                ]
            ]);

        $response->assertOk();

        $register       =   Register::where( 'name', 'Test Cash Register' )->first();

        /**
         * Opening cash register
         */
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/cash-registers/open/' . $register->id, [
                'amount'    =>  100
            ]);

        $response->assertStatus(200);

        /**
         * cashing on the cash register
         */
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/cash-registers/' . RegisterHistory::ACTION_CASHING . '/' . $register->id, [
                'amount'    =>  100
            ]);

        $response->assertStatus(200);

        /**
         * cashout on the cash register
         */
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/cash-registers/' . RegisterHistory::ACTION_CASHOUT . '/' . $register->id, [
                'amount'    =>  100
            ]);

        $response->assertStatus(200);

        /**
         * close cash register
         */
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/cash-registers/' . RegisterHistory::ACTION_CLOSING . '/' . $register->id, [
                'amount'    =>  100
            ]);

        $response->assertStatus(200);
    }
}
