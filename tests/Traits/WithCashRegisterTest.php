<?php
namespace Tests\Traits;

use App\Exceptions\NotAllowedException;
use App\Models\Register;
use App\Models\RegisterHistory;
use App\Services\CashRegistersService;

trait WithCashRegisterTest
{
    protected function attemptCreateCashRegisterWithActions()
    {
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

    protected function attemptCreateRegisterTransactions()
    {
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

    protected function attemptCreateRegister()
    {
        $response       =   $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/crud/ns.registers', [
                'name'                  =>  __( 'Register' ),
                'general'               =>  [
                    'status'            =>  Register::STATUS_CLOSED
                ]
            ]);

        $response->assertJson([
            'status'    =>  'success'
        ]);

        global $argv;

        $argv       =   json_decode( $response->getContent(), true );
    }

    protected function attemptDeleteRegister()
    {
        global $argv;
        
        $response       =   $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'DELETE', 'api/nexopos/v4/crud/ns.registers/' . $argv[ 'entry' ][ 'id' ], [
                'name'                  =>  __( 'Register' ),
                'general'               =>  [
                    'status'            =>  Register::STATUS_CLOSED
                ]
            ]);        

            $response->assertJson([
                'status'    =>  'success'
            ]);
    }
}