<?php

namespace Modules\PayTheFly\Models;

use App\Models\NsModel;
use App\Models\Order;

/**
 * @property int         $id
 * @property int         $order_id
 * @property string      $serial_no
 * @property string|null $tx_hash
 * @property string      $chain_symbol
 * @property string|null $wallet
 * @property string      $value
 * @property string|null $fee
 * @property int         $tx_type
 * @property bool        $confirmed
 * @property string      $project_id
 * @property string|null $raw_payload
 */
class PayTheFlyTransaction extends NsModel
{
    protected $table = 'nexopos_paythefly_transactions';

    protected $fillable = [
        'order_id',
        'serial_no',
        'tx_hash',
        'chain_symbol',
        'wallet',
        'value',
        'fee',
        'tx_type',
        'confirmed',
        'project_id',
        'raw_payload',
    ];

    protected $casts = [
        'confirmed' => 'boolean',
        'tx_type'   => 'integer',
    ];

    /**
     * Relationship: the NexoPOS order this transaction belongs to.
     */
    public function order()
    {
        return $this->belongsTo( Order::class, 'order_id' );
    }
}
