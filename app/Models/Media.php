<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Media extends NsModel
{
    use HasFactory;
    
    protected $table    =   'nexopos_medias';

    public function user()
    {
        return $this->belongsTo( User::class );
    }
}
