<?php

namespace Modules\NsOxen\Models;

use App\Models\ProductCategory as NexoPOSProductCategory;

class ProductCategory extends NexoPOSProductCategory
{
    protected $fillable = [
        'name',
        'parent_id',
        'description',
    ];
}
