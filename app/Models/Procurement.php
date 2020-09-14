<?php
namespace App\Models;

use App\Events\ProcurementAfterDeleteEvent;
use App\Events\ProcurementBeforeDeleteEvent;
use App\Models\Provider;
use Illuminate\Database\Eloquent\Model;

class Procurement extends Model
{
    protected $table    =   'nexopos_' . 'procurements';

    protected $dispatchesEvents     =   [
        'deleting'      =>  ProcurementBeforeDeleteEvent::class,
        'deleted'       =>  ProcurementAfterDeleteEvent::class,
    ];

    public function products()
    {
        return $this->hasMany( ProcurementProduct::class, 'procurement_id' );
    }

    public function provider()
    {
        return $this->belongsTo( Provider::class );
    }
}