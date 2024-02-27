<?php

namespace App\Events;

use App\Models\Product;
use Illuminate\Queue\SerializesModels;

class ProductAfterDeleteEvent
{
    use SerializesModels;

    public function __construct( public Product $product )
    {
        // ...
    }
}
