<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
}