<?php

namespace Tests\Traits;

use App\Classes\Currency;
use App\Models\ActiveTransactionHistory;
use App\Models\DashboardDay;
use App\Models\Procurement;
use App\Models\Transaction;
use App\Models\TransactionAccount;
use App\Models\TransactionHistory;
use App\Services\ReportService;
use App\Services\TestService;
use App\Services\TransactionService;
use Illuminate\Support\Facades\Auth;

trait WithAccountingTest
{
    use WithProcurementTest;

    public function createDefaultAccounts()
    {
        /**
         * @var TransactionService $service
         */
        $service    =   app()->make( TransactionService::class );
        $service->createDefaultAccounts();

        /**
         * We'll now check if all options are set
         */
        $this->assertTrue( ( $procurementPaid = ns()->option->get( 'ns_accounting_procurement_unpaid_account', false ) ) !== false, sprintf( __( 'No settings for "%s" was set.' ), 'ns_accounting_procurement_unpaid_account' ) );
        $this->assertTrue( ( $procurementPaidAccount = ns()->option->get( 'ns_accounting_procurement_paid_account', false ) ) !== false, sprintf( __( 'No settings for "%s" was set.' ), 'ns_accounting_procurement_paid_account' ) );
        $this->assertTrue( ( $orderCashAccount = ns()->option->get( 'ns_accounting_orders_cash_account', false ) ) !== false, sprintf( __( 'No settings for "%s" was set.' ), 'ns_accounting_orders_cash_account' ) );
        $this->assertTrue( ( $orderCashAccount = ns()->option->get( 'ns_accounting_orders_refund_account', false ) ) !== false, sprintf( __( 'No settings for "%s" was set.' ), 'ns_accounting_orders_cash_account' ) );
        $this->assertTrue( ( $ordersCogsAccount = ns()->option->get( 'ns_accounting_orders_cogs_account', false ) ) !== false, sprintf( __( 'No settings for "%s" was set.' ), 'ns_accounting_orders_cogs_account' ) );
    }

    public function attemptUnpaidProcurement()
    {
        $unpaidProcurementAccountId = ns()->option->get( 'ns_accounting_procurement_unpaid_account' );
        $unpaidProcurementAccount   = TransactionAccount::find( $unpaidProcurementAccountId );
        $offsetAccount = TransactionAccount::find( $unpaidProcurementAccount->counter_account_id );

        $this->assertTrue( $unpaidProcurementAccount instanceof TransactionAccount, __( 'No unpaid procurement account was found.' ) );
        $this->assertTrue( $offsetAccount instanceof TransactionAccount, __( 'No offset account was found.' ) );
        $this->assertTrue( $unpaidProcurementAccount->histories()->count() === 0, __( 'Unpaid procurement account has transactions.' ) );

        $response = $this->attemptCreateAnUnpaidProcurement();

        $transaction     =   TransactionHistory::where( 'procurement_id', $response[ 'data' ][ 'procurement' ][ 'id' ] )->first();
        $offsetTransaction  =   TransactionHistory::where( 'reflection_source_id', $transaction->id )->where( 'is_reflection', true )->first();

        $this->assertTrue( $transaction->transaction_account_id === $unpaidProcurementAccountId, __( 'Transaction account is not the unpaid procurement account.' ) );
        $this->assertTrue( $unpaidProcurementAccount->histories()->count() === 1, __( 'Unpaid procurement account has no transactions.' ) );
        $this->assertTrue( $transaction instanceof TransactionHistory, __( 'No transaction history was found.' ) );
        $this->assertTrue( $offsetTransaction instanceof TransactionHistory, __( 'No offset transaction history was found.' ) );
        $this->assertTrue( $offsetTransaction->transaction_account_id === $offsetAccount->id, __( 'Offset transaction account is not the offset account.' ) );
    }

    public function attemptPaidProcurement()
    {
        $paidProcurementAccountId   =   ns()->option->get( 'ns_accounting_procurement_paid_account' );
        $paidProcurementAccount     =   TransactionAccount::find( $paidProcurementAccountId );
        $offsetAccount              =   TransactionAccount::find( $paidProcurementAccount->counter_account_id );

        $this->assertTrue( $paidProcurementAccount instanceof TransactionAccount, __( 'No paid procurement account was found.' ) );
        $this->assertTrue( $paidProcurementAccount->histories()->count() === 0, __( 'Paid procurement account has transactions.' ) );

        $response = $this->attemptCreateProcurement();

        $transaction     =   TransactionHistory::where( 'procurement_id', $response[ 'data' ][ 'procurement' ][ 'id' ] )->first();
        $offsetTransaction  =   TransactionHistory::where( 'reflection_source_id', $transaction->id )->where( 'is_reflection', true )->first();

        $this->assertTrue( $paidProcurementAccount->histories()->count() === 1, __( 'Paid procurement account has no transactions.' ) );
        $this->assertTrue( $transaction instanceof TransactionHistory, __( 'No transaction history was found.' ) );
        $this->assertTrue( $offsetTransaction instanceof TransactionHistory, __( 'No offset transaction history was found.' ) );
        $this->assertTrue( $offsetTransaction->transaction_account_id === $offsetAccount->id, __( 'Offset transaction account is not the offset account.' ) );
    }

    public function attemptDeleteProcurement()
    {
        $response = $this->attemptCreateProcurement();

        /**
         * let's check if history exists
         */
        $firstTransaction = TransactionHistory::where( 'procurement_id', $response[ 'data' ][ 'procurement' ][ 'id' ] )->first();

        $this->assertTrue( $firstTransaction instanceof TransactionHistory, __( 'No transaction history was found.' ) );

        $this->attemptDeleteProcurementWithId( $response[ 'data' ][ 'procurement' ][ 'id' ] );

        /**
         * history should be deleted with it's reflection
         */
        $countTransaction = TransactionHistory::where( 'procurement_id', $response[ 'data' ][ 'procurement' ][ 'id' ] )->count();

        $this->assertTrue( $countTransaction === 0, __( 'Transaction history was not deleted.' ) );
        $this->assertTrue( TransactionHistory::where( 'is_reflection', 1 )->where( 'reflection_source_id', $firstTransaction->id )->first() === null, __( 'Reflection transaction history was not deleted.' ) );
    }
}
