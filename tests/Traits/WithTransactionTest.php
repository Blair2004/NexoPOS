<?php

namespace Tests\Traits;

use App\Models\Role;
use App\Models\Transaction;
use App\Models\TransactionAccount;
use App\Models\TransactionHistory;
use Illuminate\Support\Facades\Auth;

trait WithTransactionTest
{
    protected function attemptCreateTransactionAccount()
    {
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/transactions-accounts', [
                'name' => __( 'Exploitation Expenses' ),
                'author' => Auth::id(),
                'account' => '000010',
                'operation' => TransactionHistory::OPERATION_DEBIT,
            ] );

        $response->assertStatus( 200 );

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/transactions-accounts', [
                'name' => __( 'Employee Salaries' ),
                'author' => Auth::id(),
                'account' => '000011',
                'operation' => TransactionHistory::OPERATION_DEBIT,
            ] );

        $response->assertStatus( 200 );

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/transactions-accounts', [
                'name' => __( 'Random Expenses' ),
                'author' => Auth::id(),
                'account' => '000012',
                'operation' => TransactionHistory::OPERATION_DEBIT,
            ] );

        $response->assertStatus( 200 );
    }

    protected function attemptCreateTransaction()
    {
        /**
         * Assuming expense category is "Exploitation Expenses"
         */
        $transactionAccount = TransactionAccount::find( 1 );

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/crud/ns.transactions', [
                'name' => __( 'Store Rent' ),
                'general' => [
                    'active' => true,
                    'value' => 1500,
                    'recurring' => false,
                    'type' => Transaction::TYPE_DIRECT,
                    'account_id' => $transactionAccount->id,
                ],
            ] );

        $response->assertJson( [
            'status' => 'success',
        ] );

        /**
         * Assuming expense category is "Exploitation Expenses"
         */
        $transactionAccount = TransactionAccount::find( 1 );

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/crud/ns.transactions', [
                'name' => __( 'Material Delivery' ),
                'general' => [
                    'active' => true,
                    'value' => 300,
                    'recurring' => false,
                    'type' => Transaction::TYPE_DIRECT,
                    'account_id' => $transactionAccount->id,
                ],
            ] );

        $response->assertJson( [
            'status' => 'success',
        ] );

        /**
         * Assuming expense category is "Exploitation Expenses"
         */
        $transactionAccount = TransactionAccount::find( 2 );

        $role = Role::get()->shuffle()->first();
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/crud/ns.transactions', [
                'name' => __( 'Store Rent' ),
                'general' => [
                    'active' => true,
                    'value' => 1500,
                    'type' => Transaction::TYPE_ENTITY,
                    'recurring' => false,
                    'account_id' => $transactionAccount->id,
                    'occurrence' => 'month_starts',
                    'group_id' => $role->id,
                ],
            ] );

        $response->assertJson( [
            'status' => 'success',
        ] );

        $response->assertStatus( 200 );
    }
}
