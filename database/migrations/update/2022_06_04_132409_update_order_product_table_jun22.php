<?php

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
        Schema::table( 'nexopos_orders_products', function ( Blueprint $table ) {
            if ( ! Schema::hasColumn( 'nexopos_orders_products', 'product_type' ) ) {
                $table->string( 'product_type' )->default( 'product' );
            }

            if ( ! Schema::hasColumn( 'nexopos_orders_products', 'rate' ) ) {
                $table->float( 'rate' )->default(0);
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
        Schema::table( 'nexopos_orders_products', function ( Blueprint $table ) {
            if ( Schema::hasColumn( 'nexopos_orders_products', 'product_type' ) ) {
                $table->dropColumn( 'product_type' );
            }

            if ( Schema::hasColumn( 'nexopos_orders_products', 'rate' ) ) {
                $table->dropColumn( 'rate' );
            }
        });
    }
};
