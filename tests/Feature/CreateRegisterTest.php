<?php

namespace Tests\Feature;

use App\Exceptions\NotAllowedException;
use App\Models\CashFlow;
use App\Models\Register;
use App\Models\RegisterHistory;
use App\Models\Role;
use App\Services\CashRegistersService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CreateRegisterTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCreateRegister()
    {
        Sanctum::actingAs(
            Role::namespace( 'admin' )->users->first(),
            ['*']
        );

        $response       =   $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/crud/ns.registers', [
                'name'                  =>  __( 'Cash Register' ),
                'general'               =>  [
                    'status'            =>  Register::STATUS_CLOSED
                ]
            ]);

        $response->assertJson([
            'status'    =>  'success'
        ]);

        $register   =   Register::orderBy( 'id', 'desc' )->first();

        /**
         * @var CashRegistersService
         */
        $cashOpeningBalance     =   0;
        $cashRegisterService    =   app()->make( CashRegistersService::class );
        $cashRegisterService->openRegister( $register, $cashOpeningBalance, 'test opening amount' );

        $registerHistory    =   RegisterHistory::where( 'register_id', $register->id )
            ->orderBy( 'id', 'desc' )
            ->first();

        $this->assertTrue( $registerHistory instanceof RegisterHistory, 'No register history were created after a closing operation' );

        if ( $registerHistory instanceof RegisterHistory ) {
            $this->assertTrue( $registerHistory->value == $cashOpeningBalance, 'The cash opening operation amount doesn\'t match' );
        }
        
        /**
         * should not be able to cash-out
         */
        try {
            $cashRegisterService->cashOut( $register, 100, 'test cash out' );
        } catch( NotAllowedException $exception ) {
            $this->assertContains( $exception->getMessage(), [ 'Not enough fund to cash out.' ]);
        }
        
        $register->refresh();
        $cashInAmount   =   200;
        $cashRegisterService->cashIn( $register, $cashInAmount, 'test' );

        $registerHistory    =   RegisterHistory::where( 'register_id', $register->id )
            ->orderBy( 'id', 'desc' )
            ->first();

        $this->assertTrue( $registerHistory instanceof RegisterHistory, 'No register history were created after a cash-in operation' );
        $this->assertTrue( $registerHistory->value == $cashInAmount, 'The cash-in operation amount doesn\'t match' );
        
        /**
         * should be able to cash-out now.
         */
        $register->refresh();
        $cashOutAmount  =   100;
        $cashRegisterService->cashOut( $register, $cashOutAmount, 'test cash-out' );

        $registerHistory    =   RegisterHistory::where( 'register_id', $register->id )
            ->orderBy( 'id', 'desc' )
            ->first();

        $this->assertTrue( $registerHistory instanceof RegisterHistory, 'No register history were created after a cash-in operation' );
        $this->assertTrue( $registerHistory->value == $cashOutAmount, 'The cash-out operation amount doesn\'t match' );

        /**
         * Closing the cash register
         */
        $register->refresh();
        $closingBalance     =   $register->balance;
        $cashRegisterService->closeRegister( $register, $register->balance, 'test close register' );

        $registerHistory    =   RegisterHistory::where( 'register_id', $register->id )
            ->orderBy( 'id', 'desc' )
            ->first();

        $this->assertTrue( $registerHistory instanceof RegisterHistory, 'No register history were created after a closing operation' );
        $this->assertTrue( $registerHistory->value == $closingBalance, 'The cash-out operation amount doesn\'t match' );
    }
}
