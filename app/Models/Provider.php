<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property integer $id
 * @property string $uuid
 * @property integer $author
 * @property string $description
 * @property float $amount_paid
 * @property \Carbon\Carbon $updated_at
*/
class Provider extends NsModel
{
    use HasFactory;

    protected $table = 'nexopos_' . 'providers';

    protected $guarded = [];

    public function procurements()
    {
        return $this->hasMany( Procurement::class );
    }
}
