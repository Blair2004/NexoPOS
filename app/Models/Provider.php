<?php

namespace App\Models;

use App\Traits\NsDependable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Provider extends NsModel
{
    use HasFactory, NsDependable;

    protected $table = 'nexopos_' . 'providers';

    protected $guarded = [];

    protected $isDependencyFor = [
        Procurement::class => [
            'local_name' => 'name',
            'local_index' => 'id',
            'foreign_name' => 'code',
            'foreign_index' => 'provider_id',
        ],
    ];

    public function procurements()
    {
        return $this->hasMany( Procurement::class );
    }
}
