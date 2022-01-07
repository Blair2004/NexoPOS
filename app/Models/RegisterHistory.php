<?php
namespace App\Models;

use App\Events\CashRegisterHistoryAfterCreatedEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int id
 * @property int register_id
 * @property string action
 * @property int author
 * @property float value
 * @property string description
 * @property string uuid
 */
class RegisterHistory extends NsModel
{
    use HasFactory;
    
    protected $table    =   'nexopos_' . 'registers_history';

    const ACTION_OPENING    =   'register-opening';
    const ACTION_CLOSING    =   'register-closing';
    const ACTION_CASHING    =   'register-cash-in';
    const ACTION_CASHOUT    =   'register-cash-out';
    const ACTION_SALE       =   'register-sale';
    const ACTION_REFUND     =   'register-refund';

    const IN_ACTIONS    =   [
        self::ACTION_CASHING,
        self::ACTION_OPENING,
        self::ACTION_SALE
    ];

    const OUT_ACTIONS   =   [
        self::ACTION_REFUND,
        self::ACTION_CLOSING,
        self::ACTION_CASHOUT
    ];

    protected $dispatchesEvents     =   [
        'created'     =>      CashRegisterHistoryAfterCreatedEvent::class
    ];
    public function scopeRegister( $query, Register $register )
    {
        return $query->where( 'register_id', $register->id );
    }

    public function scopeAction( $query, $action ) 
    {
        return $query->where( 'action', $action );
    }

    public function scopeFrom( $query, $date )
    {
        return $query->where( 'created_at', '>=', $date );
    }

    public function scopeTo( $query, $date )
    {
        return $query->where( 'created_at', '<=', $date );
    }
}