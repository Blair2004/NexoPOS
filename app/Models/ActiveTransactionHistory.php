<?php

namespace App\Models;

/**
 * @property int            $id
 * @property mixed          $transaction_id
 * @property string         $operation
 * @property mixed          $is_reflection
 * @property mixed          $reflection_source_id
 * @property mixed          $transaction_account_id
 * @property mixed          $procurement_id
 * @property mixed          $order_refund_id
 * @property mixed          $order_payment_id
 * @property mixed          $order_refund_product_id
 * @property mixed          $order_id
 * @property mixed          $order_product_id
 * @property mixed          $register_history_id
 * @property mixed          $customer_account_history_id
 * @property string         $name
 * @property string         $type
 * @property string         $status
 * @property float          $value
 * @property \Carbon\Carbon $trigger_date
 * @property mixed          $rule_id
 * @property mixed          $author
 * @property mixed          $created_at
 * @property mixed          $updated_at
 */
class ActiveTransactionHistory extends TransactionHistory
{
    protected static function booted()
    {
        static::addGlobalScope( 'active', function ( $builder ) {
            $builder->where( 'status', TransactionHistory::STATUS_ACTIVE );
        } );
    }
}
