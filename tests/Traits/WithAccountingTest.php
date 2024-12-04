<?php

namespace Tests\Traits;

use App\Models\Order;
use App\Models\Procurement;
use App\Models\TransactionAccount;
use App\Models\TransactionActionRule;
use App\Models\TransactionHistory;
use App\Services\TestService;
use App\Services\TransactionService;
use Exception;

trait WithAccountingTest
{
    use WithProcurementTest;

    public function createDefaultAccounts()
    {
        /**
         * @var TransactionService $service
         */
        $service = app()->make( TransactionService::class );
        $service->createDefaultAccounts();

        /**
         * @todo test to perform here
         */
        $this->assertTrue( TransactionAccount::count() > 0 );
        $this->assertTrue( TransactionActionRule::count() > 0 );
    }

    public function attemptTestAccountingForProcurement( Procurement $procurement )
    {
        $history = $procurement->transactionHistories()->first();
        $rule = TransactionActionRule::findOrFail( $history->rule_id );

        $procurementTransactionHistory = TransactionHistory::where( 'transaction_account_id', $rule->account_id )
            ->where( 'procurement_id', $procurement->id )
            ->first();

        $procurementTransactionHistoryOffset = TransactionHistory::where( 'reflection_source_id', $procurementTransactionHistory->id )
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
            ->where( 'rule_id', $rule->id )
            ->firstOrFail();

        $procurementTransactionHistoryOffset = TransactionHistory::where( 'reflection_source_id', $procurementTransactionHistory->id )
            ->where( 'is_reflection', true )
            ->where( 'transaction_account_id', $rule->offset_account_id )
            ->first();

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

    public function attemptTestAccountingForOrder( $order )
    {
        $order = $order instanceof Order ? $order : Order::findOrFail( $order );
        $ruleOn = match ( $order->payment_status ) {
            Order::PAYMENT_UNPAID => TransactionActionRule::RULE_ORDER_UNPAID,
            Order::PAYMENT_PAID => TransactionActionRule::RULE_ORDER_PAID,
            default => null,
        };

        $history = TransactionHistory::where( 'order_id', $order->id )->first();
        $rule = TransactionActionRule::where( 'id', $history->rule_id )->where( 'on', $ruleOn )->first();

        $this->testOrderRule( $order, $rule );
        $this->assertTrue( TransactionActionRule::findOrFail( $history->rule_id ) instanceof TransactionActionRule, __( 'No transaction action rule was found.' ) );
    }

    public function attemptTestAccountingForVoidedOrder( $order, $rule )
    {
        $rule = TransactionActionRule::where( 'on', $rule )->first();

        $history = TransactionHistory::where( 'order_id', $order->id )->first();

        $this->testOrderRule( $order, $rule );
        $this->assertTrue( TransactionActionRule::findOrFail( $history->rule_id ) instanceof TransactionActionRule, __( 'No transaction action rule was found.' ) );
    }

    public function attemptTestAccountingForDeletedOrder( $order )
    {
        $this->assertTrue( TransactionHistory::where( 'order_id', $order->id )->count() === 0, __( 'Transaction history was found after the order was deleted.' ) );
    }

    public function attemptTestAccountingForOrderCogs( $order )
    {
        $rule = TransactionActionRule::where( 'on', TransactionActionRule::RULE_ORDER_COGS )->first();

        [ $orderTransactionHistory, $orderTransactionHistoryOffset ] = $this->testOrderRule( $order, $rule );

        $this->assertTrue( $orderTransactionHistory->value === $order->total_cogs, __( 'The total cogs doesnt match the transaction value' ) );
        $this->assertTrue( $orderTransactionHistoryOffset->value === $order->total_cogs, __( 'The total cogs doesnt match the transaction value' ) );
    }

    private function testOrderRule( $order, $rule )
    {
        $orderTransactionHistory = TransactionHistory::where( 'transaction_account_id', $rule->account_id )
            ->where( 'order_id', $order->id )
            ->where( 'rule_id', $rule->id )
            ->first();

        $this->assertTrue( $orderTransactionHistory instanceof TransactionHistory, __( 'No transaction history was found.' ) );

        $orderTransactionHistoryOffset = TransactionHistory::where( 'reflection_source_id', $orderTransactionHistory->id )
            ->where( 'is_reflection', true )
            ->where( 'transaction_account_id', $rule->offset_account_id )
            ->first();

        $this->assertTrue( $rule instanceof TransactionActionRule, __( 'No rule was found for this action.' ) );
        $this->assertTrue( $orderTransactionHistoryOffset instanceof TransactionHistory, __( 'No offset transaction history was found.' ) );

        return [ $orderTransactionHistory, $orderTransactionHistoryOffset ];
    }

    public function attemptTestAccountingForUnpaidToPaidOrder( $order )
    {
        $rule = TransactionActionRule::where( 'on', TransactionActionRule::RULE_ORDER_FROM_UNPAID_TO_PAID )
            ->first();

        $history = TransactionHistory::where( 'order_id', $order->id )
            ->where( 'rule_id', $rule->id )
            ->first();

        $this->testOrderRule( $order, $rule );
        $this->assertTrue( TransactionActionRule::findOrFail( $history->rule_id ) instanceof TransactionActionRule, __( 'No transaction action rule was found.' ) );
    }
}
