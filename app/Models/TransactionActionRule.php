<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransactionActionRule extends Model
{
    use HasFactory;

    public const RULE_PROCUREMENT_PAID = 'procurement_paid';

    public const RULE_PROCUREMENT_PARTIALLY_PAID = 'procurement_partially_paid';

    public const RULE_PROCUREMENT_UNPAID = 'procurement_unpaid';

    public const RULE_PROCUREMENT_FROM_UNPAID_TO_PAID = 'procurement_from_unpaid_to_paid';

    public const RULE_PRODUCT_DAMAGED = 'product_damaged';

    public const RULE_PRODUCT_RETURNED = 'product_returned';

    public const RULE_ORDER_PAID = 'order_paid';

    public const RULE_ORDER_PARTIALLY_PAID = 'order_partially_paid';

    public const RULE_ORDER_UNPAID = 'order_unpaid';

    public const RULE_ORDER_REFUNDED = 'order_refunded';

    public const RULE_ORDER_PARTIALLY_REFUNDED = 'order_partially_refunded';

    public const RULE_ORDER_COGS = 'order_cogs';

    public const RULE_ORDER_FROM_UNPAID_TO_PAID = 'order_from_unpaid_to_paid';

    public const RULE_ORDER_PAID_VOIDED = 'order_paid_voided';

    public const RULE_ORDER_UNPAID_VOIDED = 'order_unpaid_voided';

    public const LEGACY_EVENTS = [
        self::RULE_PROCUREMENT_PAID,
        self::RULE_PROCUREMENT_PARTIALLY_PAID,
        self::RULE_PROCUREMENT_UNPAID,
        self::RULE_PROCUREMENT_FROM_UNPAID_TO_PAID,
        self::RULE_PRODUCT_DAMAGED,
        self::RULE_PRODUCT_RETURNED,
        self::RULE_ORDER_PAID,
        self::RULE_ORDER_PARTIALLY_PAID,
        self::RULE_ORDER_UNPAID,
        self::RULE_ORDER_REFUNDED,
        self::RULE_ORDER_PARTIALLY_REFUNDED,
        self::RULE_ORDER_COGS,
        self::RULE_ORDER_FROM_UNPAID_TO_PAID,
        self::RULE_ORDER_PAID_VOIDED,
        self::RULE_ORDER_UNPAID_VOIDED,
    ];

    protected $table = 'nexopos_transactions_actions_rules';

    protected $fillable = [
        'on',
        'action',
        'account_id',
        'do',
        'offset_account_id',
        'locked',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'locked' => 'boolean',
            'active' => 'boolean',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany( TransactionActionRuleLine::class, 'rule_id' )->orderBy( 'display_order' );
    }

    public function journals(): HasMany
    {
        return $this->hasMany( AccountingJournal::class, 'rule_id' );
    }
}
