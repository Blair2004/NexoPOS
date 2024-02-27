<?php
/**
 * Table Migration
**/

use App\Classes\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if ( ! Schema::hasTable( 'nexopos_orders_products' ) ) {
            Schema::createIfMissing( 'nexopos_orders_products', function ( Blueprint $table ) {
                $table->bigIncrements( 'id' );
                $table->string( 'name' );
                $table->string( 'unit_name' )->nullable();
                $table->string( 'mode' )->default( 'normal' ); //
                $table->string( 'product_type' )->default( 'product' ); // group
                $table->integer( 'product_id' );
                $table->integer( 'order_id' );
                $table->integer( 'unit_id' );
                $table->integer( 'unit_quantity_id' );
                $table->integer( 'product_category_id' );
                $table->integer( 'procurement_product_id' )->nullable();
                $table->integer( 'tax_group_id' )->default( 0 );
                $table->string( 'tax_type' )->default( 0 );
                $table->string( 'uuid' )->nullable();
                $table->string( 'status' )->default( 'sold' ); // sold, refunded
                $table->text( 'return_observations' )->nullable();
                $table->string( 'return_condition' )->nullable();
                $table->string( 'discount_type' )->default( 'none' );
                $table->float( 'discount', 18, 5 )->default( 0 );
                $table->float( 'quantity', 18, 5 ); // could be the base unit
                $table->float( 'discount_percentage', 18, 5 )->default( 0 );
                $table->float( 'unit_price', 18, 5 )->default( 0 );
                $table->float( 'price_with_tax', 18, 5 )->default( 0 );
                $table->float( 'price_without_tax', 18, 5 )->default( 0 );
                $table->float( 'wholesale_tax_value', 18, 5 )->default( 0 );
                $table->float( 'sale_tax_value', 18, 5 )->default( 0 );
                $table->float( 'tax_value', 18, 5 )->default( 0 );
                $table->float( 'rate' )->default( 0 );
                $table->float( 'total_price', 18, 5 )->default( 0 );
                $table->float( 'total_price_with_tax', 18, 5 )->default( 0 );
                $table->float( 'total_price_without_tax', 18, 5 )->default( 0 );
                $table->float( 'total_purchase_price', 18, 5 )->default( 0 );
                $table->timestamps();
            } );
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if ( Schema::hasTable( 'nexopos_orders_products' ) ) {
            Schema::dropIfExists( 'nexopos_orders_products' );
        }
    }
};
