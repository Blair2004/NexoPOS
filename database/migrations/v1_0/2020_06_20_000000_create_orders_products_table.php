<?php
/**
 * Table Migration
 * @package  5.0
**/

use App\Classes\Hook;
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
        if ( ! Schema::hasTable( Hook::filter( 'ns-table-prefix', 'nexopos_orders_products' ) ) ) {
            Schema::create( Hook::filter( 'ns-table-prefix', 'nexopos_orders_products' ), function( Blueprint $table ) {
                $table->bigIncrements( 'id' );
                $table->string( 'name' );
                $table->integer( 'product_id' );
                $table->integer( 'unit_id' );
                $table->integer( 'unit_quantity_id' );
                $table->integer( 'order_id' );
                $table->float( 'quantity' ); // could be the base unit
                $table->string( 'discount_type' )->default( 'none' );
                $table->float( 'discount' )->default(0);
                $table->float( 'discount_percentage' )->default(0);
                $table->float( 'gross_price' )->default(0);
                $table->float( 'unit_price' )->default(0);
                $table->integer( 'tax_group_id' )->default(0);
                $table->string( 'tax_type' )->default(0);
                $table->string( 'wholesale_tax_value' )->default(0);
                $table->string( 'sale_tax_value' )->default(0);
                $table->float( 'tax_value' )->default(0);
                $table->float( 'net_price' )->default(0);
                $table->string( 'mode' )->default( 'retail' );
                $table->string( 'unit_name' )->nullable();
                // $table->float( 'base_quantity' );
                $table->float( 'total_gross_price' )->default(0);
                $table->float( 'total_price' )->default(0);
                $table->float( 'total_purchase_price' )->default(0);
                $table->string( 'return_condition' );
                $table->text( 'return_observations' );
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
        if ( Schema::hasTable( Hook::filter( 'ns-table-prefix', 'nexopos_orders_products' ) ) ) {
            Schema::dropIfExists( Hook::filter( 'ns-table-prefix', 'nexopos_orders_products' ) );
        }
    }
}

