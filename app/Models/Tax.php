<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int    $id
 * @property string $uuid
 * @property string $description
 * @property float  $rate
 * @property int    $author_id
 * @property Carbon $updated_at
 */
class Tax extends NsModel
{
    use HasFactory;

    protected $table = 'nexopos_' . 'taxes';

    public function group()
    {
        $this->belongsTo( Group::class, 'tax_group_id', 'id' );
    }

    public function user()
    {
        return $this->belongsTo( User::class, 'author_id' );
    }
}
