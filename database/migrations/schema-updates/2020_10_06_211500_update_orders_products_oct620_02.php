<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use App\Classes\Schema;

class UpdateOrdersProductsOct62002 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table( 'nexopos_orders_products', function( Blueprint $table ) {
            if ( Schema::hasColumn( 'nexopos_orders_products', 'discount_amount' ) ) {
                $table->renameColumn( 'discount_amount', 'discount' );
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
            if ( Schema::hasColumn( 'nexopos_orders_products', 'discount' ) ) {
                $table->renameColumn( 'discount', 'discount_amount' );
            }
        });
    }
}
