<?php
/**
 * Table Migration
 * @package  5.0
**/
use Illuminate\Support\Facades\Schema;
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
            Schema::create( 'nexopos_orders_products', function( Blueprint $table ) {
                $table->bigIncrements( 'id' );
                $table->string( 'name' );
                $table->integer( 'product_id' );
                $table->integer( 'order_id' );
                $table->float( 'quantity' ); // could be the base unit
                $table->string( 'discount_type' )->default( 'none' );
                $table->float( 'discount_amount' )->default(0);
                $table->float( 'discount_percentage' )->default(0);
                $table->float( 'gross_price' )->default(0);
                $table->float( 'sale_price' )->default(0);
                $table->integer( 'tax_id' )->default(0);
                $table->string( 'tax_type' )->default(0);
                $table->float( 'tax_value' )->default(0);
                $table->float( 'net_price' )->default(0);
                // $table->float( 'base_quantity' );
                $table->float( 'total_gross_price' );
                $table->float( 'total_price' );
                $table->float( 'total_net_price' );
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
            Schema::drop( 'nexopos_orders_products' );
        }
    }
}

