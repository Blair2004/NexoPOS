<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class UnitGroup extends NsModel
{
    use HasFactory;

    protected $guarded = [];

    protected $table = 'nexopos_units_groups';

    protected $isDependencyFor = [
        Unit::class => [
            'local_name' => 'name',
            'local_index' => 'id',
            'foreign_name' => 'name',
            'foreign_index' => 'group_id',
        ],
    ];

    public function units()
    {
        return $this->hasMany( Unit::class, 'group_id' );
    }
}
