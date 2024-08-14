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
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/crud/ns.cash-registers', [
                'name' => __( 'Test Cash Register' ),
                'general' => [
                    'status' => Register::STATUS_CLOSED,
                ],
            ] );

        $response->assertOk();

        $register = Register::where( 'name', 'Test Cash Register' )->first();

        /**
         * Opening cash register
         */
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/cash-registers/open/' . $register->id, [
                'amount' => 100,
            ] );

        $response->assertStatus( 200 );

        /**
         * cashing on the cash register
         */
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/cash-registers/' . RegisterHistory::ACTION_CASHING . '/' . $register->id, [
                'amount' => 100,
                'transaction_account_id' => ns()->option->get( 'ns_accounting_default_cashing_account', 0 ),
            ] );

        $response->assertStatus( 200 );

        /**
         * cashout on the cash register
         */
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/cash-registers/' . RegisterHistory::ACTION_CASHOUT . '/' . $register->id, [
                'amount' => 100,
                'transaction_account_id' => ns()->option->get( 'ns_accounting_default_cashout_account', 0 ),
            ] );

        $response->assertStatus( 200 );

        /**
         * close cash register
         */
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/cash-registers/' . RegisterHistory::ACTION_CLOSING . '/' . $register->id, [
                'amount' => 100,
            ] );

        $response->assertStatus( 200 );
    }

    protected function attemptCreateRegisterTransactions()
    {
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/crud/ns.cash-registers', [
                'name' => __( 'Cash Register' ),
                'general' => [
                    'status' => Register::STATUS_CLOSED,
                ],
            ] );

        $response->assertJson( [
            'status' => 'success',
        ] );

        $register = Register::orderBy( 'id', 'desc' )->first();

        $cashOpeningBalance = 100;

        /**
         * @var CashRegistersService $cashRegisterService
         */
        $cashRegisterService = app()->make( CashRegistersService::class );
        $cashRegisterService->openRegister( $register, $cashOpeningBalance, 'test opening amount' );

        $registerHistory = RegisterHistory::where( 'register_id', $register->id )
            ->orderBy( 'id', 'desc' )
            ->first();

        $this->assertTrue( $registerHistory instanceof RegisterHistory, 'No register history were created after a closing operation' );

        if ( $registerHistory instanceof RegisterHistory ) {
            $this->assertTrue( $registerHistory->value == $cashOpeningBalance, 'The cash opening operation amount doesn\'t match' );
        }

        $register->refresh();
        $this->assertTrue( $register->balance == $cashOpeningBalance, 'The register balance doesn\'t match' );

        /**
         * should not be able to cash-out
         */
        try {
            $cashRegisterService->cashOut( $register, 100, ns()->option->get( 'ns_accounting_default_cashout_account', 0 ), 'test cash out' );
        } catch ( NotAllowedException $exception ) {
            $this->assertContains( $exception->getMessage(), [ 'Not enough fund to cash out.' ] );
        }

        $register->refresh();
        $cashInAmount = 200;
        $cashRegisterService->cashIng( $register, $cashInAmount, ns()->option->get( 'ns_accounting_default_cashout_account', 0 ), 'test' );

        $registerHistory = RegisterHistory::where( 'register_id', $register->id )
            ->orderBy( 'id', 'desc' )
            ->first();

        $this->assertTrue( $registerHistory instanceof RegisterHistory, 'No register history were created after a cash-in operation' );
        $this->assertTrue( $registerHistory->value == $cashInAmount, 'The cash-in operation amount doesn\'t match' );

        /**
         * should be able to cash-out now.
         */
        $register->refresh();
        $cashOutAmount = 100;
        $cashRegisterService->cashOut( $register, $cashOutAmount, ns()->option->get( 'ns_accounting_default_cashout_account', 0 ), 'test cash-out' );

        $registerHistory = RegisterHistory::where( 'register_id', $register->id )
            ->orderBy( 'id', 'desc' )
            ->first();

        $this->assertTrue( $registerHistory instanceof RegisterHistory, 'No register history were created after a cash-in operation' );
        $this->assertTrue( $registerHistory->value == $cashOutAmount, 'The cash-out operation amount doesn\'t match' );

        /**
         * Closing the cash register
         */
        $register->refresh();
        $closingBalance = $register->balance;
        $cashRegisterService->closeRegister( $register, $register->balance, 'test close register' );

        $registerHistory = RegisterHistory::where( 'register_id', $register->id )
            ->orderBy( 'id', 'desc' )
            ->first();

        $this->assertTrue( $registerHistory instanceof RegisterHistory, 'No register history were created after a closing operation' );
        $this->assertTrue( $registerHistory->value == $closingBalance, 'The cash-out operation amount doesn\'t match' );

        return $register;
    }

    protected function attemptCashInRegister( Register $register )
    {
        $initialBalance = $register->balance;

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/cash-registers/' . RegisterHistory::ACTION_CASHING . '/' . $register->id, [
                'amount' => 100,
                'transaction_account_id' => ns()->option->get( 'ns_accounting_default_cashing_account', 0 ),
            ] );

        $response->assertStatus( 200 );

        /**
         * We'll check the current balande of the register
         */
        $register->refresh();

        $this->assertTrue( $register->balance == 100 + $initialBalance, 'The register balance doesn\'t match' );
    }

    protected function attemptCashOutRegister( Register $register )
    {
        $register->refresh();

        $initialBalance = $register->balance;

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/cash-registers/' . RegisterHistory::ACTION_CASHOUT . '/' . $register->id, [
                'amount' => 100,
                'transaction_account_id' => ns()->option->get( 'ns_accounting_default_cashout_account', 0 ),
            ] );

        $response->assertStatus( 200 );

        /**
         * We'll check the current balande of the register
         */
        $register->refresh();

        $this->assertTrue( $register->balance == $initialBalance - 100, 'The register balance doesn\'t match' );
    }

    protected function attemptCloseRegisterWithInvalidAmount()
    {
        $register = $this->attemptCreateRegister();

        /**
         * We'll first open the cash register with
         * a valid amount.
         */
        $this->attemptOpenRegister( $register );

        /**
         * We'll then attempt to close the register
         * with an invalid amount.
         */
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/cash-registers/close/' . $register->id, [
                'amount' => 50,
            ] );

        $response->asserOk();

        /**
         * We should check the register history and check
         * if that claim a shortage
         */
        $transactionHistory = RegisterHistory::where( 'register_id', $register->id )
            ->where( 'action', RegisterHistory::ACTION_CLOSING )
            ->orderBy( 'id', 'desc' )
            ->first();

        $this->assertTrue( $transactionHistory->transaction_type === 'positive', 'The transaction type doesn\'t match' );
    }

    protected function attemptOpenRegister( Register $register )
    {
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/cash-registers/open/' . $register->id, [
                'amount' => 100,
            ] );

        $response->assertStatus( 200 );

        /**
         * We'll check the current balande of the register
         */
        $register->refresh();
        $this->assertTrue( $register->balance == 100, 'The register balance doesn\'t match' );

        /**
         * We'll check if there is an history created for that register
         */
        $registerHistory = RegisterHistory::where( 'register_id', $register->id )
            ->orderBy( 'id', 'desc' )
            ->first();

        $this->assertTrue( $registerHistory instanceof RegisterHistory, 'No register history were created after a closing operation' );

        /**
         * The last register history transaction should match
         * the transaction we've registered above.
         */
        $this->assertTrue( $registerHistory->value == 100, 'The cash opening operation amount doesn\'t match' );
    }

    protected function attemptCloseRegister( Register $register )
    {
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/cash-registers/close/' . $register->id, [
                'amount' => $register->balance,
            ] );

        $response->assertStatus( 200 );

        /**
         * We'll check the current balande of the register
         */
        $register->refresh();
        $this->assertTrue( $register->balance == 0, 'The register balance doesn\'t match' );

        /**
         * We'll check if there is an history created for that register
         */
        $registerHistory = RegisterHistory::where( 'register_id', $register->id )
            ->where( 'action', RegisterHistory::ACTION_CLOSING )
            ->orderBy( 'id', 'desc' )
            ->first();

        $this->assertTrue( $registerHistory instanceof RegisterHistory, 'No register history were created after a closing operation' );
        $this->assertTrue( $registerHistory->transaction_type === 'unchanged', 'The closing balance doesn\'t match the register balance' );
    }

    protected function attemptUpdateRegister( Register $register )
    {
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'PUT', 'api/crud/ns.cash-registers/' . $register->id, [
                'name' => $register->name . ' updated',
                'general' => [
                    'status' => Register::STATUS_CLOSED,
                ],
            ] );

        $response->assertJson( [
            'status' => 'success',
        ] );

        return $register;
    }

    protected function attemptCreateRegister()
    {
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/crud/ns.cash-registers', [
                'name' => __( 'Register' ),
                'general' => [
                    'status' => Register::STATUS_CLOSED,
                ],
            ] );

        $response->assertJson( [
            'status' => 'success',
        ] );

        $data = $response->json();

        return Register::find( $data[ 'data' ][ 'entry' ][ 'id' ] );
    }

    protected function attemptDeleteRegister( Register $register )
    {
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'DELETE', 'api/crud/ns.cash-registers/' . $register->id );

        $response->assertJson( [
            'status' => 'success',
        ] );
    }
}
