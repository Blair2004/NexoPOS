<?php

use App\Classes\Hook;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use App\Classes\Schema;

class AddPriceModeOnOrdersProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table( 'nexopos_orders_products', function( Blueprint $table ) {
            if ( ! Schema::hasColumn( 'nexopos_orders_products', 'mode' ) ) {
                $table->string( 'mode' )->default( 'normal' ); // can be wholesale
            }
            if ( ! Schema::hasColumn( 'nexopos_orders_products', 'unit_name' ) ) {
                $table->string( 'unit_name' )->nullable(); // can be wholesale
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
        if ( Schema::hascolumn( 'nexopos_orders_products', 'mode' ) ) {
            Schema::table( 'nexopos_orders_products', function( Blueprint $table ) {
                $table->dropColumn( 'mode' );
            });
        }

        if ( Schema::hascolumn( 'nexopos_orders_products', 'unit_name' ) ) {
            Schema::table( 'nexopos_orders_products', function( Blueprint $table ) {
                $table->dropColumn( 'unit_name' );
            });
        }
    }
}
