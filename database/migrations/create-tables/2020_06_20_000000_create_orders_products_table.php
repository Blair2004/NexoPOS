<?php
/**
 * Table Migration
 * @package  5.0
**/

use App\Classes\Hook;
use App\Classes\Schema;;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrdersProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return  void
     */
    public function up()
    {
        if ( ! Schema::hasTable( 'nexopos_orders_products' ) ) {
            Schema::createIfMissing( 'nexopos_orders_products', function( Blueprint $table ) {
                $table->bigIncrements( 'id' );
                $table->string( 'name' );
                $table->integer( 'product_id' );
                $table->integer( 'product_category_id' );
                $table->integer( 'procurement_product_id' )->nullable();
                $table->integer( 'unit_id' );
                $table->integer( 'unit_quantity_id' );
                $table->integer( 'order_id' );
                $table->float( 'quantity', 18, 5 ); // could be the base unit
                $table->string( 'discount_type' )->default( 'none' );
                $table->float( 'discount', 18, 5 )->default(0);
                $table->float( 'discount_percentage', 18, 5 )->default(0);
                $table->float( 'gross_price', 18, 5 )->default(0);
                $table->float( 'unit_price', 18, 5 )->default(0);
                $table->integer( 'tax_group_id' )->default(0);
                $table->string( 'tax_type' )->default(0);
                $table->string( 'wholesale_tax_value' )->default(0);
                $table->string( 'sale_tax_value' )->default(0);
                $table->float( 'tax_value', 18, 5 )->default(0);
                $table->float( 'net_price', 18, 5 )->default(0);
                $table->string( 'mode' )->default( 'normal' ); // 
                $table->string( 'unit_name' )->nullable();
                $table->float( 'total_gross_price', 18, 5 )->default(0);
                $table->float( 'total_price', 18, 5 )->default(0);
                $table->float( 'total_net_price', 18, 5 )->default(0);
                $table->float( 'total_purchase_price', 18, 5 )->default(0);
                $table->string( 'return_condition' )->nullable();
                $table->text( 'return_observations' )->nullable();
                $table->string( 'uuid' )->nullable();
                $table->string( 'status' )->default( 'sold' ); // sold, refunded
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return  void
     */
    public function down()
    {
        if ( Schema::hasTable( 'nexopos_orders_products' ) ) {
            Schema::dropIfExists( 'nexopos_orders_products' );
        }
    }
}

