<?php

use App\Classes\Cache;
use App\Classes\Currency;
use App\Models\OrderProduct;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public ProductService $productService;

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->productService = app()->make( ProductService::class );

        /**
         * will ensure a single product
         * is handled every time.
         */
        $cached = json_decode( Cache::get( 'fix_wrong_purchase_prices', '[]' ) );

        OrderProduct::whereNotIn( 'id', $cached )->get()->each( function ( OrderProduct $orderProduct ) use ( $cached ) {
            $product = Product::find( $orderProduct->product_id );
            $lastPurchasePrice = $this->productService->getLastPurchasePrice(
                product: $product,
                unit: $orderProduct->unit,
                before: $orderProduct->created_at
            );
            $orderProduct->total_purchase_price = Currency::fresh( $lastPurchasePrice )->multipliedBy( $orderProduct->quantity )->toFloat();
            $orderProduct->save();

            $cached[] = $orderProduct->id;

            Cache::put( 'fix_wrong_purchase_prices', json_encode( $cached ), now()->addDay() );
        } );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
