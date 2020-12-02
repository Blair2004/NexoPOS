<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Unit extends NsModel 
{
    use HasFactory;
    
    protected $table = 'nexopos_units';
    protected $casts    =   [
        'base_unit'     =>  'boolean'
    ];

    public function group()
    {
        return $this->belongsTo( UnitGroup::class, 'group_id' );
    }
} 