<?php

namespace App\Models;

use App\Events\CustomerAccountHistoryAfterCreatedEvent;
use App\Events\CustomerAccountHistoryAfterUpdatedEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int    $id
 * @property string $customer_id
 * @property int    $order_id
 * @property float  $amount
 * @property string $operation
 * @property int    $author
 * @property string $description
 */
class CustomerAccountHistory extends NsModel
{
    use HasFactory;

    const OPERATION_DEDUCT = 'deduct';

    const OPERATION_REFUND = 'refund';

    const OPERATION_ADD = 'add';

    const OPERATION_PAYMENT = 'payment';

    protected $table = 'nexopos_' . 'customers_account_history';

    public $dispatchesEvents = [
        'created' => CustomerAccountHistoryAfterCreatedEvent::class,
        'updated' => CustomerAccountHistoryAfterUpdatedEvent::class,
    ];

    public function customer()
    {
        return $this->hasOne( Customer::class, 'id', 'customer_id' );
    }
}
