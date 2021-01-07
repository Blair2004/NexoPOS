<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use App\Classes\Schema;

class UpdateNexoposOrdersProductsOct18 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table( 'nexopos_orders_products', function( Blueprint $table ){
            if ( ! Schema::hasColumn( 'nexopos_orders_products', 'unit_quantity_id' ) ) {
                $table->float( 'unit_quantity_id' );
            }

            if ( Schema::hasColumn( 'nexopos_orders_products', 'sale_price' ) ) {
                $table->renameColumn( 'sale_price', 'unit_price' );
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
        Schema::table( 'nexopos_orders_products', function( Blueprint $table ){
            if ( Schema::hasColumn( 'nexopos_orders_products', 'unit_quantity_id' ) ) {
                $table->dropColumn( 'unit_quantity_id' );
            }

            if ( Schema::hasColumn( 'nexopos_orders_products', 'unit_price' ) ) {
                $table->renameColumn( 'unit_price', 'sale_price' );
            }
        });
    }
}
