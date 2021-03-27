<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UnitGroup extends NsModel 
{
    use HasFactory;
    
    protected $guarded  =   [];
    
    protected $table = 'nexopos_units_groups';

    public function units()
    {
        return $this->hasMany( Unit::class, 'group_id' );
    }
} 