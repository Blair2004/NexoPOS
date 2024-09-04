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
         * @todo test to perform here
         */
    }

    public function createProcurementsAccounts()
    {
        /**
         * @var TransactionService $service
         */
        $service    =   app()->make( TransactionService::class );
        $service->createProcurementAccounts();

        /**
         * We'll now check if all options are set
         */
        // $this->assertTrue( ( $procurementPaid = ns()->option->get( 'ns_accounting_procurement_unpaid_account', false ) ) !== false, sprintf( __( 'No settings for "%s" was set.' ), 'ns_accounting_procurement_unpaid_account' ) );
        // $this->assertTrue( ( $procurementPaidAccount = ns()->option->get( 'ns_accounting_procurement_paid_account', false ) ) !== false, sprintf( __( 'No settings for "%s" was set.' ), 'ns_accounting_procurement_paid_account' ) );
    }

    public function attemptUnpaidProcurement()
    {
        $response = $this->attemptCreateAnUnpaidProcurement();        
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
}
