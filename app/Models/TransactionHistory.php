<?php

namespace App\Models;

use App\Events\TransactionsHistoryAfterCreatedEvent;
use App\Events\TransactionsHistoryAfterDeletedEvent;
use App\Events\TransactionsHistoryAfterUpdatedEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property integer $id
 * @property integer $transaction_id
 * @property mixed $operation
 * @property integer $transaction_account_id
 * @property integer $procurement_id
 * @property integer $order_refund_id
 * @property integer $order_id
 * @property integer $register_history_id
 * @property integer $customer_account_history_id
 * @property mixed $name
 * @property mixed $status
 * @property float $value
 * @property integer $author
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
*/
class TransactionHistory extends NsModel
{
    use HasFactory;

    protected $table = 'nexopos_' . 'transactions_histories';

    const STATUS_ACTIVE = 'active';

    const STATUS_DELETING = 'deleting';

    const OPERATION_DEBIT = 'debit';

    const OPERATION_CREDIT = 'credit';

    /**
     * Unique account identifier for sales.
     */
    const ACCOUNT_SALES = '001';

    /**
     * Unique account identifier for every stocked procurement.
     */
    const ACCOUNT_PROCUREMENTS = '002';

    /**
     * Unique account identifier for refunded sales.
     */
    const ACCOUNT_REFUNDS = '003';

    /**
     * Unique account identifier for cash register cash in.
     */
    const ACCOUNT_REGISTER_CASHIN = '004';

    /**
     * Unique account identifier for cash register cash out.
     */
    const ACCOUNT_REGISTER_CASHOUT = '005';

    /**
     * Unique identifier for spoiled goods.
     */
    const ACCOUNT_SPOILED = '006';

    /**
     * Unique identifier for customer credit credit.
     */
    const ACCOUNT_CUSTOMER_CREDIT = '007';

    /**
     * Unique identifier for customer credit debit.
     */
    const ACCOUNT_CUSTOMER_DEBIT = '008';

    protected $dispatchesEvents = [
        'created' => TransactionsHistoryAfterCreatedEvent::class,
        'updated' => TransactionsHistoryAfterUpdatedEvent::class,
        'deleted' => TransactionsHistoryAfterDeletedEvent::class,
    ];

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

    public function scopeTo( $query, $date )
    {
        return $query->where( 'created_at', '<=', $date );
    }
}
