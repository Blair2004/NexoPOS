<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    protected $table    =   'nexopos_medias';

    public function user()
    {
        return $this->belongsTo( User::class );
    }
}
