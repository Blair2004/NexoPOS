<?php
namespace App\Models;

use App\Models\Provider;
use Illuminate\Database\Eloquent\Model;

class Procurement extends Model
{
    protected $table    =   'nexopos_' . 'procurements';

    public function products()
    {
        return $this->hasMany( ProcurementProduct::class, 'procurement_id' );
    }

    public function provider()
    {
        return $this->belongsTo( Provider::class );
    }
}