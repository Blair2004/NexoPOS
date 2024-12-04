<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionActionRule extends Model
{
    use HasFactory;

    protected $fillable = [ 'on', 'action', 'account_id', 'do', 'offset_account_id', 'locked' ];

    const RULE_PROCUREMENT_PAID = 'procurement_paid';

    const RULE_PROCUREMENT_PARTIALLY_PAID = 'procurement_partially_paid';

    const RULE_PROCUREMENT_UNPAID = 'procurement_unpaid';

    const RULE_PROCUREMENT_FROM_UNPAID_TO_PAID = 'procurement_from_unpaid_to_paid';

    const RULE_PRODUCT_DAMAGED = 'product_damaged';

    const RULE_PRODUCT_RETURNED = 'product_returned';

    const RULE_ORDER_PAID = 'order_paid';

    const RULE_ORDER_PARTIALLY_PAID = 'order_partially_paid';

    const RULE_ORDER_UNPAID = 'order_unpaid';

    const RULE_ORDER_REFUNDED = 'order_refunded';

    const RULE_ORDER_PARTIALLY_REFUNDED = 'order_partially_refunded';

    const RULE_ORDER_COGS = 'order_cogs';

    const RULE_ORDER_FROM_UNPAID_TO_PAID = 'order_from_unpaid_to_paid';

    const RULE_ORDER_PAID_VOIDED = 'order_paid_voided';

    const RULE_ORDER_UNPAID_VOIDED = 'order_unpaid_voided';

    protected $table = 'nexopos_transactions_actions_rules';
}
