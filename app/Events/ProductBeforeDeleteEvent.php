<?php
namespace App\Events;

use App\Models\Product;
use Illuminate\Queue\SerializesModels;;

class ProductBeforeDeleteEvent
{
    use SerializesModels;

    public $product;

    public function __construct( Product $product )
    {
        $this->product  =   $product;
    }
}