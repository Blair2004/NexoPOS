<?php

namespace App\Observers;

use App\Models\ProcurementProduct;
use App\Models\ProductHistory;

class ProcurementProductObserver
{
    //
    public function create( ProcurementProduct $product )
    {
        $history                            =   new ProductHistory();
        $history->procurement_id            =   $product->procurement_id;
        $history->product_id                =   $product->product_id;
        $history->procurement_product_id    =   $product->id;
        $history->operation_type            =   'procurement';
        $history->quantity                  =   $product->quantity;
        $history->unit_price                =   $product->purchase_price;
        $history->total_price               =   $product->total_purchase_price;
        $history->unit_id                   =   $product->unit_id;
        $history->save();
    }
}
