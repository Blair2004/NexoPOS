<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use App\Classes\Schema;
use App\Models\OrderProduct;

class UpdateOrderProductsTableMar30 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table( 'nexopos_orders_products', function( Blueprint $table ) {
            if ( ! Schema::hasColumn( 'nexopos_orders_products', 'product_category_id' ) ) {
                $table->integer( 'product_category_id' )->default(0);
            }
        });

        OrderProduct::get()->each( function( $product ) {
            $product->product_category_id   =   $product->product->category->id ?? 0;  
            $product->save();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table( 'nexopos_orders_products', function( Blueprint $table ) {
            if ( Schema::hasColumn( 'neoxpos_orders_products', 'product_category_id' ) ) {
                $table->dropColumn( 'product_category_id' );
            }
        });
    }
}
