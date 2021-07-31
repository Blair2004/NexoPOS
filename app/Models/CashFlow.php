<?php

namespace App\Models;

use App\Events\CashFlowAfterCreatedEvent;
use App\Events\CashFlowAfterUpdatedEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashFlow extends NsModel
{
    use HasFactory;

    protected $table = 'nexopos_cash_flow_history';

    protected $dispatchesEvents     =   [
        'saved'     =>  CashFlowAfterCreatedEvent::class,
        'updated'   =>  CashFlowAfterUpdatedEvent::class,
    ];
}
