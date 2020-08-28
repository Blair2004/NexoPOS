<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnitGroup extends Model 
{
    protected $table = 'nexopos_units_groups';

    public function units()
    {
        return $this->hasMany( Unit::class, 'group_id' );
    }
} 