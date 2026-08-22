<?php

namespace App\Accounting;

use App\Models\TransactionHistory;

class AccountingEventCatalog
{
    public const ORDER_FINALIZED = 'order_finalized';

    public const ORDER_PAYMENT = 'order_payment';

    public const ORDER_REFUND = 'order_refund';

    public const ORDER_VOID = 'order_void';

    public const RETURN_GOOD = 'product_return_good';

    public const RETURN_DAMAGED = 'product_return_damaged';

    public const PROCUREMENT_RECEIPT = 'procurement_receipt';

    public const PROCUREMENT_PAYMENT = 'procurement_payment';

    public const ADJUSTMENT_NEGATIVE = 'stock_adjustment_negative';

    public const ADJUSTMENT_POSITIVE = 'stock_adjustment_positive';

    public const WALLET_ADDITION = 'wallet_addition';

    public const WALLET_DEDUCTION = 'wallet_deduction';

    /**
     * @return array<string, array{label: string, amount_sources: array<string, string>, dynamic_account_roles: array<string, array{label: string, operation: string}>}>
     */
    public function all(): array
    {
        return [
            self::ORDER_FINALIZED => $this->event( __( 'Finalized Order' ), [
                'total' => __( 'Order Total' ),
                'net_sale' => __( 'Net Sale' ),
                'tax' => __( 'Sales Tax' ),
                'cogs' => __( 'Cost of Goods Sold' ),
            ] ),
            self::ORDER_PAYMENT => $this->event( __( 'Order Payment' ), [ 'payment_amount' => __( 'Applied Payment Amount' ) ], [
                'payment_account' => [ 'label' => __( 'Payment Method Account' ), 'operation' => TransactionHistory::OPERATION_DEBIT ],
            ] ),
            self::ORDER_REFUND => $this->event( __( 'Order Refund' ), [
                'net_refund' => __( 'Net Refund' ),
                'refunded_tax' => __( 'Refunded Tax' ),
                'refund_unpaid' => __( 'Receivable Portion' ),
                'refund_paid' => __( 'Paid Portion' ),
            ], [
                'refund_payment_account' => [ 'label' => __( 'Refund Payment Account' ), 'operation' => TransactionHistory::OPERATION_CREDIT ],
            ] ),
            self::RETURN_GOOD => $this->event( __( 'Good-condition Return' ), [ 'refund_cost' => __( 'Original Cost' ) ] ),
            self::RETURN_DAMAGED => $this->event( __( 'Damaged Return' ), [ 'refund_cost' => __( 'Original Cost' ) ] ),
            self::PROCUREMENT_RECEIPT => $this->event( __( 'Procurement Receipt' ), [ 'procurement_cost' => __( 'Procurement Cost' ) ] ),
            self::PROCUREMENT_PAYMENT => $this->event( __( 'Procurement Payment' ), [ 'payment_amount' => __( 'Payment Amount' ) ] ),
            self::ADJUSTMENT_NEGATIVE => $this->event( __( 'Negative Stock Adjustment' ), [ 'adjustment_cost' => __( 'Adjustment Cost' ) ] ),
            self::ADJUSTMENT_POSITIVE => $this->event( __( 'Positive Stock Adjustment' ), [ 'adjustment_cost' => __( 'Adjustment Cost' ) ] ),
            self::WALLET_ADDITION => $this->event( __( 'Wallet Addition' ), [ 'wallet_amount' => __( 'Wallet Amount' ) ] ),
            self::WALLET_DEDUCTION => $this->event( __( 'Wallet Deduction' ), [ 'wallet_amount' => __( 'Wallet Amount' ) ] ),
        ];
    }

    public function has( string $event ): bool
    {
        return isset( $this->all()[ $event ] );
    }

    /**
     * @return array{label: string, amount_sources: array<string, string>, dynamic_account_roles: array<string, array{label: string, operation: string}>}|null
     */
    public function get( string $event ): ?array
    {
        return $this->all()[ $event ] ?? null;
    }

    public function operation( string $categoryIdentifier, string $effect ): string
    {
        return config( "accounting.accounts.{$categoryIdentifier}.{$effect}" );
    }

    /**
     * @param  array<string, string>                                                                                                                      $amountSources
     * @param  array<string, array{label: string, operation: string}>                                                                                     $dynamicRoles
     * @return array{label: string, amount_sources: array<string, string>, dynamic_account_roles: array<string, array{label: string, operation: string}>}
     */
    private function event( string $label, array $amountSources, array $dynamicRoles = [] ): array
    {
        return [
            'label' => $label,
            'amount_sources' => $amountSources,
            'dynamic_account_roles' => $dynamicRoles,
        ];
    }
}
