<?php

namespace App\Models;

use App\Traits\NsDependable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int            $id
 * @property string         $uuid
 * @property int            $author
 * @property string         $description
 * @property float          $amount_paid
 * @property \Carbon\Carbon $updated_at
 */
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
