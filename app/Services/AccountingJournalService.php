<?php

namespace App\Services;

use App\Accounting\AccountingEventCatalog;
use App\Exceptions\NotAllowedException;
use App\Models\AccountingJournal;
use App\Models\CustomerAccountHistory;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\OrderProductRefund;
use App\Models\OrderRefund;
use App\Models\PaymentType;
use App\Models\Procurement;
use App\Models\ProcurementProduct;
use App\Models\ProductHistory;
use App\Models\ProductUnitQuantity;
use App\Models\Transaction;
use App\Models\TransactionAccount;
use App\Models\TransactionActionRule;
use App\Models\TransactionHistory;
use Illuminate\Support\Facades\DB;

class AccountingJournalService
{
    public function __construct( private AccountingEventCatalog $catalog ) {}

    /**
     * @param array<string, float|int|string>                                   $amounts
     * @param array<string, array{account: TransactionAccount, effect: string}> $dynamicRoles
     * @param array<string, int|null>                                           $sourceColumns
     */
    public function post(
        string $event,
        string $sourceType,
        string|int $sourceId,
        string $name,
        array $amounts,
        array $dynamicRoles = [],
        array $sourceColumns = [],
        ?int $authorId = null,
        mixed $triggerDate = null,
    ): ?AccountingJournal {
        $existing = AccountingJournal::query()
            ->where( 'source_type', $sourceType )
            ->where( 'source_id', (string) $sourceId )
            ->where( 'event', $event )
            ->first();

        if ( $existing instanceof AccountingJournal ) {
            return $existing->load( 'lines.account' );
        }

        $rule = TransactionActionRule::query()
            ->where( 'on', $event )
            ->where( 'active', true )
            ->with( 'lines.account' )
            ->first();

        if ( ! $rule instanceof TransactionActionRule || $rule->lines->isEmpty() ) {
            return null;
        }

        return DB::transaction( function () use ( $event, $sourceType, $sourceId, $name, $amounts, $dynamicRoles, $sourceColumns, $authorId, $triggerDate, $rule ): AccountingJournal {
            $journal = AccountingJournal::query()->firstOrCreate(
                [
                    'source_type' => $sourceType,
                    'source_id' => (string) $sourceId,
                    'event' => $event,
                ],
                [
                    'rule_id' => $rule->id,
                    'name' => $name,
                    'status' => AccountingJournal::STATUS_POSTED,
                    'author_id' => $authorId ?? $this->validAuthorId(),
                    'posted_at' => $triggerDate ?? ns()->date->toDateTimeString(),
                ]
            );

            if ( ! $journal->wasRecentlyCreated ) {
                return $journal->load( 'lines.account' );
            }

            $debits = 0.0;
            $credits = 0.0;
            $postedLines = 0;

            foreach ( $rule->lines as $line ) {
                $amount = round( (float) ( $amounts[ $line->amount_source ] ?? 0 ), 5 );

                if ( $amount < 0 ) {
                    throw new NotAllowedException( __( 'Accounting journal amounts cannot be negative.' ) );
                }

                if ( $amount === 0.0 ) {
                    continue;
                }

                if ( $line->dynamic_account_role ) {
                    $dynamic = $dynamicRoles[ $line->dynamic_account_role ] ?? null;

                    if ( $dynamic === null ) {
                        throw new NotAllowedException( __( 'A required dynamic accounting account could not be resolved.' ) );
                    }

                    $account = $dynamic['account'];
                    $effect = $dynamic['effect'];
                } else {
                    $account = $line->account;
                    $effect = $line->effect;
                }

                if ( ! $account instanceof TransactionAccount ) {
                    throw new NotAllowedException( __( 'An accounting rule references an account that no longer exists.' ) );
                }

                $operation = $this->catalog->operation( $account->category_identifier, $effect );
                $debits += $operation === TransactionHistory::OPERATION_DEBIT ? $amount : 0;
                $credits += $operation === TransactionHistory::OPERATION_CREDIT ? $amount : 0;

                TransactionHistory::query()->create( array_merge( [
                    'journal_id' => $journal->id,
                    'rule_id' => $rule->id,
                    'operation' => $operation,
                    'transaction_account_id' => $account->id,
                    'name' => $name,
                    'type' => Transaction::TYPE_INDIRECT,
                    'status' => TransactionHistory::STATUS_ACTIVE,
                    'value' => $amount,
                    'trigger_date' => $triggerDate ?? ns()->date->toDateTimeString(),
                    'author_id' => $authorId ?? $this->validAuthorId(),
                ], $sourceColumns ) );

                $postedLines++;
            }

            if ( $postedLines < 2 || round( $debits - $credits, 5 ) !== 0.0 ) {
                throw new NotAllowedException( __( 'The computed accounting journal is not balanced.' ) );
            }

            return $journal->load( 'lines.account' );
        }, attempts: 3 );
    }

    public function postFinalizedOrder( Order $order ): ?AccountingJournal
    {
        if ( in_array( $order->payment_status, [ Order::PAYMENT_HOLD, Order::PAYMENT_VOID ], true ) ) {
            return null;
        }

        $journal = $this->post(
            AccountingEventCatalog::ORDER_FINALIZED,
            Order::class,
            $order->id,
            sprintf( __( 'Finalized Order: %s' ), $order->code ),
            [
                'total' => $order->total,
                'net_sale' => max( 0, (float) $order->total - (float) $order->total_tax_value ),
                'tax' => $order->total_tax_value,
                'cogs' => $order->total_cogs,
            ],
            sourceColumns: [ 'order_id' => $order->id ],
            authorId: $order->author_id,
            triggerDate: $order->created_at,
        );

        if ( $journal instanceof AccountingJournal ) {
            $order->payments->each( fn( OrderPayment $payment ) => $this->postOrderPayment( $payment ) );
        }

        return $journal;
    }

    public function postOrderPayment( OrderPayment $payment ): ?AccountingJournal
    {
        $order = $payment->order;

        if ( ! $order instanceof Order || ! AccountingJournal::query()->where( 'source_type', Order::class )->where( 'source_id', (string) $order->id )->where( 'event', AccountingEventCatalog::ORDER_FINALIZED )->where( 'status', AccountingJournal::STATUS_POSTED )->exists() ) {
            return null;
        }

        $appliedBefore = TransactionHistory::query()
            ->whereNotNull( 'journal_id' )
            ->where( 'order_id', $order->id )
            ->whereNotNull( 'order_payment_id' )
            ->where( 'operation', TransactionHistory::OPERATION_CREDIT )
            ->sum( 'value' );
        $amount = min( (float) $payment->value, max( 0, (float) $order->total - (float) $appliedBefore ) );

        if ( $amount <= 0 ) {
            return null;
        }

        [ $account, $effect ] = $this->paymentMapping( $payment->identifier );

        return $this->post(
            AccountingEventCatalog::ORDER_PAYMENT,
            OrderPayment::class,
            $payment->id,
            sprintf( __( 'Order Payment: %s' ), $order->code ),
            [ 'payment_amount' => $amount ],
            [ 'payment_account' => [ 'account' => $account, 'effect' => $effect ] ],
            [ 'order_id' => $order->id, 'order_payment_id' => $payment->id ],
            $payment->author_id,
            $payment->created_at,
        );
    }

    public function postRefund( OrderRefund $refund ): ?AccountingJournal
    {
        $order = $refund->order;
        $receivablesAccountId = TransactionAccount::query()
            ->where( 'system_identifier', 'accounts_receivable' )
            ->value( 'id' );
        $paymentsApplied = (float) TransactionHistory::query()
            ->where( 'order_id', $order->id )
            ->whereNotNull( 'order_payment_id' )
            ->where( 'operation', TransactionHistory::OPERATION_CREDIT )
            ->sum( 'value' );
        $refundsAppliedToReceivables = (float) TransactionHistory::query()
            ->where( 'order_id', $order->id )
            ->whereNotNull( 'order_refund_id' )
            ->where( 'transaction_account_id', $receivablesAccountId )
            ->where( 'operation', TransactionHistory::OPERATION_CREDIT )
            ->sum( 'value' );
        $outstanding = max( 0, (float) $order->total - $paymentsApplied - $refundsAppliedToReceivables );
        $unpaid = min( (float) $refund->total, $outstanding );
        $paid = max( 0, (float) $refund->total - $unpaid );
        [ $account, $incomingEffect ] = $this->paymentMapping( $refund->payment_method );

        return $this->post(
            AccountingEventCatalog::ORDER_REFUND,
            OrderRefund::class,
            $refund->id,
            sprintf( __( 'Order Refund: %s' ), $order->code ),
            [
                'net_refund' => max( 0, (float) $refund->total - (float) $refund->tax_value ),
                'refunded_tax' => $refund->tax_value,
                'refund_unpaid' => $unpaid,
                'refund_paid' => $paid,
            ],
            [ 'refund_payment_account' => [ 'account' => $account, 'effect' => $this->invertEffect( $incomingEffect ) ] ],
            [ 'order_id' => $order->id, 'order_refund_id' => $refund->id ],
            $refund->author_id,
            $refund->created_at,
        );
    }

    public function postReturnedProduct( OrderProductRefund $refund ): ?AccountingJournal
    {
        $orderProduct = $refund->orderProduct;
        $unitCost = (float) $orderProduct->quantity > 0 ? (float) $orderProduct->total_purchase_price / (float) $orderProduct->quantity : 0;
        $event = $refund->condition === OrderProductRefund::CONDITION_UNSPOILED
            ? AccountingEventCatalog::RETURN_GOOD
            : AccountingEventCatalog::RETURN_DAMAGED;

        return $this->post(
            $event,
            OrderProductRefund::class,
            $refund->id,
            __( 'Returned Product Cost' ),
            [ 'refund_cost' => $unitCost * (float) $refund->quantity ],
            sourceColumns: [
                'order_id' => $refund->order_id,
                'order_product_id' => $refund->order_product_id,
                'order_refund_id' => $refund->order_refund_id,
                'order_refund_product_id' => $refund->id,
            ],
            authorId: $refund->author_id,
            triggerDate: $refund->created_at,
        );
    }

    public function postProcurementReceipt( Procurement $procurement ): ?AccountingJournal
    {
        return $this->post(
            AccountingEventCatalog::PROCUREMENT_RECEIPT,
            Procurement::class,
            $procurement->id,
            sprintf( __( 'Procurement Receipt: %s' ), $procurement->name ),
            [ 'procurement_cost' => $procurement->cost ],
            sourceColumns: [ 'procurement_id' => $procurement->id ],
            authorId: $procurement->author_id,
            triggerDate: $procurement->created_at,
        );
    }

    public function postProcurementPayment( Procurement $procurement ): ?AccountingJournal
    {
        return $this->post(
            AccountingEventCatalog::PROCUREMENT_PAYMENT,
            Procurement::class,
            $procurement->id,
            sprintf( __( 'Procurement Payment: %s' ), $procurement->name ),
            [ 'payment_amount' => $procurement->cost ],
            sourceColumns: [ 'procurement_id' => $procurement->id ],
            authorId: $procurement->author_id,
            triggerDate: $procurement->updated_at,
        );
    }

    public function postOrderVoid( Order $order ): ?AccountingJournal
    {
        $existing = AccountingJournal::query()
            ->where( 'source_type', Order::class )
            ->where( 'source_id', (string) $order->id )
            ->where( 'event', AccountingEventCatalog::ORDER_VOID )
            ->first();

        if ( $existing instanceof AccountingJournal ) {
            return $existing->load( 'lines.account' );
        }

        $originalLines = TransactionHistory::query()
            ->where( 'order_id', $order->id )
            ->whereNotNull( 'journal_id' )
            ->whereHas( 'journal', fn( $query ) => $query
                ->where( 'status', AccountingJournal::STATUS_POSTED )
                ->where( 'event', '!=', AccountingEventCatalog::ORDER_VOID ) )
            ->get();

        if ( $originalLines->isEmpty() ) {
            return null;
        }

        return DB::transaction( function () use ( $order, $originalLines ): AccountingJournal {
            $journal = AccountingJournal::query()->create( [
                'source_type' => Order::class,
                'source_id' => (string) $order->id,
                'event' => AccountingEventCatalog::ORDER_VOID,
                'rule_id' => null,
                'name' => sprintf( __( 'Voided Order: %s' ), $order->code ),
                'status' => AccountingJournal::STATUS_POSTED,
                'author_id' => $order->author_id ?: $this->validAuthorId(),
                'posted_at' => ns()->date->toDateTimeString(),
            ] );

            foreach ( $originalLines as $line ) {
                TransactionHistory::query()->create( [
                    'journal_id' => $journal->id,
                    'operation' => $line->operation === TransactionHistory::OPERATION_DEBIT
                        ? TransactionHistory::OPERATION_CREDIT
                        : TransactionHistory::OPERATION_DEBIT,
                    'transaction_account_id' => $line->transaction_account_id,
                    'order_id' => $order->id,
                    'name' => $journal->name,
                    'type' => Transaction::TYPE_INDIRECT,
                    'status' => TransactionHistory::STATUS_ACTIVE,
                    'value' => $line->value,
                    'trigger_date' => $journal->posted_at,
                    'author_id' => $journal->author_id,
                ] );
            }

            AccountingJournal::query()
                ->whereIn( 'id', $originalLines->pluck( 'journal_id' )->unique() )
                ->update( [ 'status' => AccountingJournal::STATUS_REVERSED ] );

            return $journal->load( 'lines.account' );
        }, attempts: 3 );
    }

    public function postOpeningBalance(): ?AccountingJournal
    {
        $existing = AccountingJournal::query()
            ->where( 'source_type', 'accounting-cutover' )
            ->where( 'source_id', '6.2.2' )
            ->where( 'event', 'opening_balance' )
            ->first();

        if ( $existing instanceof AccountingJournal ) {
            return $existing->load( 'lines.account' );
        }

        $inventory = (float) ProductUnitQuantity::query()
            ->selectRaw( 'COALESCE(SUM(quantity * cogs), 0) AS valuation' )
            ->value( 'valuation' );
        $receivables = Order::query()
            ->whereNotIn( 'payment_status', [ Order::PAYMENT_HOLD, Order::PAYMENT_VOID ] )
            ->withSum( 'payments', 'value' )
            ->get()
            ->sum( fn( Order $order ): float => max( 0, (float) $order->total - (float) $order->payments_sum_value ) );
        $payables = (float) Procurement::query()
            ->where( 'delivery_status', Procurement::STOCKED )
            ->where( 'payment_status', Procurement::PAYMENT_UNPAID )
            ->sum( 'cost' );
        $customerDeposits = (float) DB::table( 'nexopos_users' )
            ->where( 'account_amount', '>', 0 )
            ->sum( 'account_amount' );

        $balances = array_filter( [
            'inventory' => [ $inventory, 'increase' ],
            'accounts_receivable' => [ $receivables, 'increase' ],
            'accounts_payable' => [ $payables, 'increase' ],
            'customer_deposits' => [ $customerDeposits, 'increase' ],
        ], fn( array $balance ): bool => round( (float) $balance[0], 5 ) > 0 );

        if ( $balances === [] ) {
            return null;
        }

        return DB::transaction( function () use ( $balances ): AccountingJournal {
            $accounts = TransactionAccount::query()
                ->whereIn( 'system_identifier', [ ...array_keys( $balances ), 'retained_earnings' ] )
                ->get()
                ->keyBy( 'system_identifier' );
            $journal = AccountingJournal::query()->create( [
                'source_type' => 'accounting-cutover',
                'source_id' => '6.2.2',
                'event' => 'opening_balance',
                'rule_id' => null,
                'name' => __( 'Accounting Cutover Opening Balance' ),
                'status' => AccountingJournal::STATUS_POSTED,
                'author_id' => $this->validAuthorId(),
                'posted_at' => ns()->date->toDateTimeString(),
            ] );
            $debits = 0.0;
            $credits = 0.0;

            foreach ( $balances as $identifier => [ $amount, $effect ] ) {
                $account = $accounts->get( $identifier );
                $operation = $this->catalog->operation( $account->category_identifier, $effect );
                $operation === TransactionHistory::OPERATION_DEBIT
                    ? $debits += (float) $amount
                    : $credits += (float) $amount;
                $this->createOpeningBalanceLine( $journal, $account, $operation, (float) $amount );
            }

            $difference = round( $debits - $credits, 5 );

            if ( $difference !== 0.0 ) {
                $retainedEarnings = $accounts->get( 'retained_earnings' );
                $operation = $difference > 0
                    ? TransactionHistory::OPERATION_CREDIT
                    : TransactionHistory::OPERATION_DEBIT;
                $this->createOpeningBalanceLine( $journal, $retainedEarnings, $operation, abs( $difference ) );
            }

            return $journal->load( 'lines.account' );
        }, attempts: 3 );
    }

    private function createOpeningBalanceLine(
        AccountingJournal $journal,
        TransactionAccount $account,
        string $operation,
        float $amount,
    ): void {
        TransactionHistory::query()->create( [
            'journal_id' => $journal->id,
            'operation' => $operation,
            'transaction_account_id' => $account->id,
            'name' => $journal->name,
            'type' => Transaction::TYPE_INDIRECT,
            'status' => TransactionHistory::STATUS_ACTIVE,
            'value' => $amount,
            'trigger_date' => $journal->posted_at,
            'author_id' => $journal->author_id,
        ] );
    }

    public function postStockAdjustment( ProductHistory $history ): ?AccountingJournal
    {
        if ( ! in_array( $history->operation_type, [
            ProductHistory::ACTION_ADDED,
            ProductHistory::ACTION_REMOVED,
            ProductHistory::ACTION_DEFECTIVE,
            ProductHistory::ACTION_LOST,
        ], true ) ) {
            return null;
        }

        $event = in_array( $history->operation_type, ProductHistory::STOCK_INCREASE, true )
            ? AccountingEventCatalog::ADJUSTMENT_POSITIVE
            : AccountingEventCatalog::ADJUSTMENT_NEGATIVE;
        $procurementCost = $history->procurement_product_id
            ? (float) ProcurementProduct::query()->whereKey( $history->procurement_product_id )->value( 'cogs' )
            : 0.0;
        $unitCost = $procurementCost > 0
            ? $procurementCost
            : (float) ProductUnitQuantity::query()
                ->where( 'product_id', $history->product_id )
                ->where( 'unit_id', $history->unit_id )
                ->value( 'cogs' );
        $adjustmentCost = abs( (float) $history->quantity ) * $unitCost;

        if ( $adjustmentCost <= 0 ) {
            return null;
        }

        return $this->post(
            $event,
            ProductHistory::class,
            $history->id,
            __( 'Stock Adjustment at Cost' ),
            [ 'adjustment_cost' => $adjustmentCost ],
            authorId: $history->author_id,
            triggerDate: $history->created_at,
        );
    }

    public function postWallet( CustomerAccountHistory $history ): ?AccountingJournal
    {
        if (
            $history->order_id
            && in_array( $history->operation, [
                CustomerAccountHistory::OPERATION_PAYMENT,
                CustomerAccountHistory::OPERATION_REFUND,
            ], true )
        ) {
            return null;
        }

        $event = in_array( $history->operation, [ CustomerAccountHistory::OPERATION_ADD, CustomerAccountHistory::OPERATION_REFUND ], true )
            ? AccountingEventCatalog::WALLET_ADDITION
            : AccountingEventCatalog::WALLET_DEDUCTION;

        return $this->post(
            $event,
            CustomerAccountHistory::class,
            $history->id,
            __( 'Customer Wallet Activity' ),
            [ 'wallet_amount' => abs( (float) $history->amount ) ],
            sourceColumns: [ 'customer_account_history_id' => $history->id, 'order_id' => $history->order_id ],
            authorId: $history->author_id,
            triggerDate: $history->created_at,
        );
    }

    /**
     * @return array{TransactionAccount, string}
     */
    private function paymentMapping( string $identifier ): array
    {
        $paymentType = PaymentType::query()->where( 'identifier', $identifier )->first();

        if ( $paymentType?->accounting_account_id ) {
            return [
                TransactionAccount::query()->findOrFail( $paymentType->accounting_account_id ),
                $paymentType->accounting_incoming_effect ?? 'increase',
            ];
        }

        $mapping = match ( $identifier ) {
            OrderPayment::PAYMENT_CASH => [ 'cash', 'increase' ],
            OrderPayment::PAYMENT_BANK => [ 'bank', 'increase' ],
            OrderPayment::PAYMENT_ACCOUNT => [ 'customer_deposits', 'decrease' ],
            default => [ 'payment_clearing', 'increase' ],
        };

        return [
            TransactionAccount::query()->where( 'system_identifier', $mapping[0] )->firstOrFail(),
            $mapping[1],
        ];
    }

    private function validAuthorId(): int
    {
        return (int) ( ns()->getValidAuthor() ?: 1 );
    }

    private function invertEffect( string $effect ): string
    {
        return $effect === 'increase' ? 'decrease' : 'increase';
    }
}
