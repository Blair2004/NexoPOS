<?php

namespace App\Models;

use App\Events\TransactionsHistoryAfterCreatedEvent;
use App\Events\TransactionsHistoryAfterDeletedEvent;
use App\Events\TransactionsHistoryAfterUpdatedEvent;
use App\Events\TransactionsHistoryBeforeDeleteEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int            $id
 * @property int            $transaction_id
 * @property mixed          $operation
 * @property int            $transaction_account_id
 * @property int            $procurement_id
 * @property int            $order_refund_id
 * @property int            $order_id
 * @property int            $register_history_id
 * @property int            $customer_account_history_id
 * @property mixed          $name
 * @property mixed          $status
 * @property string         $type
 * @property \Carbo\Carbon  $trigger_date
 * @property float          $value
 * @property int            $author
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class TransactionHistory extends NsModel
{
    use HasFactory;

    protected $table = 'nexopos_' . 'transactions_histories';

    const STATUS_ACTIVE = 'active';

    const STATUS_DELETING = 'deleting';

    const STATUS_PENDING = 'pending';

    const OPERATION_DEBIT = 'debit';

    const OPERATION_CREDIT = 'credit';

    public $fillable = [
        'transaction_id',
        'operation',
        'transaction_account_id',
        'procurement_id',
        'order_refund_id',
        'order_refund_product_id',
        'order_id',
        'order_product_id',
        'register_history_id',
        'customer_account_history_id',
        'name',
        'type',
        'status',
        'value',
        'trigger_date',
    ];

    protected $dispatchesEvents = [
        'created' => TransactionsHistoryAfterCreatedEvent::class,
        'updated' => TransactionsHistoryAfterUpdatedEvent::class,
        'deleting' => TransactionsHistoryBeforeDeleteEvent::class,
        'deleted' => TransactionsHistoryAfterDeletedEvent::class,
    ];

    public function order()
    {
        return $this->hasOne( Order::class, 'id', 'order_id' );
    }

    public function rule()
    {
        return $this->hasOne( TransactionActionRule::class, 'id', 'rule_id' );
    }

    protected function casts()
    {
        return [
            'is_reflection' => 'boolean',
        ];
    }

    public function cashRegisterHistory()
    {
        return $this->hasOne( RegisterHistory::class, 'id', 'register_history_id' );
    }

    public function orderRefund()
    {
        return $this->hasOne( OrderRefund::class, 'id', 'order_refund_id' );
    }

    public function customerAccount()
    {
        return $this->hasOne( CustomerAccountHistory::class, 'id', 'customer_account_history_id' );
    }

    public function procurement()
    {
        return $this->hasOne( Procurement::class, 'id', 'procurement_id' );
    }

    public function transaction()
    {
        return $this->belongsTo( Transaction::class, 'transaction_id' );
    }

    public function scopeFrom( $query, $date )
    {
        return $query->where( 'created_at', '>=', $date );
    }

    public function scopeOperation( $query, $operation )
    {
        return $query->where( 'operation', $operation );
    }

    public function scopeType( $query, $operation )
    {
        return $query->where( 'type', $operation );
    }

    public function scopeScheduled( $query )
    {
        return $query->where( 'status', self::STATUS_PENDING );
    }

    public function scopeTriggerDate( $query, $date )
    {
        return $query->where( 'trigger_date', '<=', $date );
    }

    public function scopeTo( $query, $date )
    {
        return $query->where( 'created_at', '<=', $date );
    }
}
