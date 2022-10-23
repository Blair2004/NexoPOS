<?php

namespace App\Events;

use App\Models\Product;
use Illuminate\Queue\SerializesModels;

class ProductResetEvent
{
    use SerializesModels;

    public function __construct( public Product $product )
    {
        // ...
    }
}
