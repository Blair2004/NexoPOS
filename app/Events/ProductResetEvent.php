<?php
namespace App\Events;

use Illuminate\Queue\SerializesModels;
use App\Models\Product;

class ProductResetEvent 
{
    use SerializesModels;

    public $product;

    public function __construct( Product $product )
    {
        $this->product  =   $product;
    }
}