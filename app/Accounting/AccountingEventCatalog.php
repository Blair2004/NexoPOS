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
     * @return array<string, array{label: string, description: string, amount_sources: array<string, string>, dynamic_account_roles: array<string, array{label: string, operation: string}>}>
     */
    public function all(): array
    {
        return [
            self::ORDER_FINALIZED => $this->event( __( 'Finalized Order' ), __( 'Recognizes the sale, tax, receivable, cost of goods sold, and inventory movement when an order is completed.' ), [
                'total' => __( 'Order Total' ),
                'net_sale' => __( 'Net Sale' ),
                'tax' => __( 'Sales Tax' ),
                'cogs' => __( 'Cost of Goods Sold' ),
            ] ),
            self::ORDER_PAYMENT => $this->event( __( 'Order Payment' ), __( 'Records the payment method used and reduces the customer’s outstanding receivable.' ), [ 'payment_amount' => __( 'Applied Payment Amount' ) ], [
                'payment_account' => [ 'label' => __( 'Payment Method Account' ), 'operation' => TransactionHistory::OPERATION_DEBIT ],
            ] ),
            self::ORDER_REFUND => $this->event( __( 'Order Refund' ), __( 'Reverses the refunded sale and tax, reduces any receivable, and records value returned to the customer.' ), [
                'net_refund' => __( 'Net Refund' ),
                'refunded_tax' => __( 'Refunded Tax' ),
                'refund_unpaid' => __( 'Receivable Portion' ),
                'refund_paid' => __( 'Paid Portion' ),
            ], [
                'refund_payment_account' => [ 'label' => __( 'Refund Payment Account' ), 'operation' => TransactionHistory::OPERATION_CREDIT ],
            ] ),
            self::RETURN_GOOD => $this->event( __( 'Good-condition Return' ), __( 'Returns sellable items to inventory at their original cost and reverses their cost of goods sold.' ), [ 'refund_cost' => __( 'Original Cost' ) ] ),
            self::RETURN_DAMAGED => $this->event( __( 'Damaged Return' ), __( 'Records the returned item’s original cost as inventory variance without restoring sellable stock.' ), [ 'refund_cost' => __( 'Original Cost' ) ] ),
            self::PROCUREMENT_RECEIPT => $this->event( __( 'Procurement Receipt' ), __( 'Recognizes received stock as inventory and records the amount owed to the supplier.' ), [ 'procurement_cost' => __( 'Procurement Cost' ) ] ),
            self::PROCUREMENT_PAYMENT => $this->event( __( 'Procurement Payment' ), __( 'Reduces the supplier payable and records the funds used to settle it.' ), [ 'payment_amount' => __( 'Payment Amount' ) ] ),
            self::ADJUSTMENT_NEGATIVE => $this->event( __( 'Negative Stock Adjustment' ), __( 'Records missing stock at cost as inventory variance and reduces inventory.' ), [ 'adjustment_cost' => __( 'Adjustment Cost' ) ] ),
            self::ADJUSTMENT_POSITIVE => $this->event( __( 'Positive Stock Adjustment' ), __( 'Adds found stock to inventory at cost and offsets inventory variance.' ), [ 'adjustment_cost' => __( 'Adjustment Cost' ) ] ),
            self::WALLET_ADDITION => $this->event( __( 'Wallet Addition' ), __( 'Records funds received and increases the customer deposit liability.' ), [ 'wallet_amount' => __( 'Wallet Amount' ) ] ),
            self::WALLET_DEDUCTION => $this->event( __( 'Wallet Deduction' ), __( 'Reduces the customer deposit liability and records the funds released from clearing.' ), [ 'wallet_amount' => __( 'Wallet Amount' ) ] ),
        ];
    }

    public function has( string $event ): bool
    {
        return isset( $this->all()[ $event ] );
    }

    /**
     * @return array{label: string, description: string, amount_sources: array<string, string>, dynamic_account_roles: array<string, array{label: string, operation: string}>}|null
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
     * @param  array<string, string>                                                                                                                                           $amountSources
     * @param  array<string, array{label: string, operation: string}>                                                                                                          $dynamicRoles
     * @return array{label: string, description: string, amount_sources: array<string, string>, dynamic_account_roles: array<string, array{label: string, operation: string}>}
     */
    private function event( string $label, string $description, array $amountSources, array $dynamicRoles = [] ): array
    {
        return [
            'label' => $label,
            'description' => $description,
            'amount_sources' => $amountSources,
            'dynamic_account_roles' => $dynamicRoles,
        ];
    }
}
