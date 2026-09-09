<?php

namespace Modules\NsOxen\Models;

use App\Models\Product as NexoPOSProduct;

class Product extends NexoPOSProduct
{
    protected $fillable = [
        'name',
        'description',
        'status',
        'category_id',
    ];
}
