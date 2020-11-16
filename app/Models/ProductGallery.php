<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductGallery extends Model
{
    protected $table    =   'nexopos_products_galleries';

    public function product()
    {
        return $this->belongsTo( Product::class );
    }
}
