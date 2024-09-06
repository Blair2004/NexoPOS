<?php

namespace Tests\Traits;

use App\Classes\Currency;
use App\Models\ActiveTransactionHistory;
use App\Models\DashboardDay;
use App\Models\Procurement;
use App\Models\Transaction;
use App\Models\TransactionAccount;
use App\Models\TransactionActionRule;
use App\Models\TransactionHistory;
use App\Services\ReportService;
use App\Services\TestService;
use App\Services\TransactionService;
use Exception;
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
        $this->assertTrue( TransactionAccount::count() > 0 );
        $this->assertTrue( TransactionActionRule::count() > 0 );
    }

    public function attemptTestAccountingForProcurement( Procurement $procurement )
    {        
        $history    =   $procurement->transactionHistories()->first();
        $rule       =   TransactionActionRule::findOrFail( $history->rule_id );

        $procurementTransactionHistory     =   TransactionHistory::where( 'transaction_account_id', $rule->account_id )
            ->where( 'procurement_id', $procurement->id )
            ->first();

        $procurementTransactionHistoryOffset    =   TransactionHistory::where( 'reflection_source_id', $procurementTransactionHistory->id )
            ->where( 'is_reflection', true )
            ->where( 'transaction_account_id', $rule->offset_account_id )
            ->first();

        if ( $procurement->payment_status === Procurement::PAYMENT_UNPAID ) {
            $this->assertTrue( $rule->on === TransactionActionRule::RULE_PROCUREMENT_UNPAID, __( 'Rule is not for unpaid procurement.' ) );
        } else {
            $this->assertTrue( $rule->on === TransactionActionRule::RULE_PROCUREMENT_PAID, __( 'Rule is not for paid procurement.' ) );
        }

        $this->assertTrue( $procurementTransactionHistory instanceof TransactionHistory, __( 'No transaction history was found.' ) );
        $this->assertTrue( $procurementTransactionHistoryOffset instanceof TransactionHistory, __( 'No offset transaction history was found.' ) );
        $this->assertTrue( TransactionActionRule::findOrFail( $history->rule_id ) instanceof TransactionActionRule, __( 'No transaction action rule was found.' ) );
    }

    public function attemptTestAccountingForPreviouslyUnpaidProcurement( Procurement $procurement )
    {
        $rule = TransactionActionRule::where( 'on', TransactionActionRule::RULE_PROCUREMENT_FROM_UNPAID_TO_PAID )->first();

        if ( ! $rule ) {
            throw new Exception( __( 'No rule found for this action.' ) );
        }

        $procurementTransactionHistory = TransactionHistory::where( 'transaction_account_id', $rule->account_id )
            ->where( 'procurement_id', $procurement->id )
            ->firstOrFail();

        $procurementTransactionHistoryOffset = TransactionHistory::where( 'reflection_source_id', $procurementTransactionHistory->id )
            ->where( 'is_reflection', true )
            ->where( 'transaction_account_id', $rule->offset_account_id )
            ->firstOrFail();

        $this->assertTrue( $procurementTransactionHistory instanceof TransactionHistory, __( 'No transaction history was found.' ) );
        $this->assertTrue( $procurementTransactionHistoryOffset instanceof TransactionHistory, __( 'No offset transaction history was found.' ) );
        $this->assertTrue( TransactionActionRule::findOrFail( $procurementTransactionHistory->rule_id ) instanceof TransactionActionRule, __( 'No transaction action rule was found.' ) );
    }

    public function attemptPaidProcurement()
    {
        /**
         * @var TestService
         */
        $testService = app()->make( TestService::class );

        $procurementsDetails = $testService->prepareProcurement( ns()->date->now(), [
            'general.payment_status' => Procurement::PAYMENT_PAID,
            'general.delivery_status' => Procurement::PENDING,
            'total_products' => 10,
        ] );

        /**
         * Query: We store the procurement with an unpaid status.
         */
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/procurements', $procurementsDetails );

        $response->assertOk();

        return $response->json();
    }
}
