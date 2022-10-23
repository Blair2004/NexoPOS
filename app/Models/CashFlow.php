<?php

namespace App\Models;

use App\Events\CashFlowHistoryAfterCreatedEvent;
use App\Events\CashFlowHistoryAfterDeletedEvent;
use App\Events\CashFlowHistoryAfterUpdatedEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CashFlow extends NsModel
{
    use HasFactory;

    protected $table = 'nexopos_' . 'cash_flow';

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
        'created' => CashFlowHistoryAfterCreatedEvent::class,
        'updated' => CashFlowHistoryAfterUpdatedEvent::class,
        'deleted' => CashFlowHistoryAfterDeletedEvent::class,
    ];

    public function expense()
    {
        return $this->belongsTo( Expense::class, 'expense_id' );
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
