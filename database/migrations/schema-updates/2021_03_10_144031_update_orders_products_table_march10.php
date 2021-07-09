<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use App\Classes\Schema;

class UpdateOrdersProductsTableMarch10 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table( 'nexopos_orders_products', function( Blueprint $table ) {
            if ( ! Schema::hasColumn( 'nexopos_orders_products', 'procurement_product_id' ) ) {
                $table->integer( 'procurement_product_id' )->nullable();
            }
        });

        Schema::table( 'nexopos_procurements_products', function( Blueprint $table ) {
            if ( ! Schema::hasColumn( 'nexopos_procurements_products', 'barcode' ) ) {
                $table->string( 'barcode' )->nullable();
            }
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
            if ( Schema::hasColumn( 'nexopos_orders_products', 'procurement_product_id' ) ) {
                $table->dropColumn( 'procurement_product_id' );
            }
        });

        Schema::table( 'nexopos_procurements_products', function( Blueprint $table ) {
            if ( Schema::hasColumn( 'nexopos_procurements_products', 'barcode' ) ) {
                $table->dropColumn( 'barcode' );
            }
        });
    }
}
