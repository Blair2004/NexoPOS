<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UnitGroup extends Model 
{
    use HasFactory;
    
    protected $table = 'nexopos_units_groups';

    public function units()
    {
        return $this->hasMany( Unit::class, 'group_id' );
    }

    /**
     * @todo ensure to update
     * the database in order for this to work.
     */
    // public function scopeCanTransform( $query )
    // {
    //     return $query->where( 'transformable', true );
    // }
} 