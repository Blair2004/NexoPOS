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
        Schema::table( 'nexopos_orders_products_refunds', function ( Blueprint $table ) {
            if ( ! Schema::hasColumn( 'nexopos_orders_products_refunds', 'tax_value' ) ) {
                $table->float( 'tax_value' )->default(0);
            }
        });

        Schema::table( 'nexopos_orders_refunds', function ( Blueprint $table ) {
            if ( ! Schema::hasColumn( 'nexopos_orders_refunds', 'tax_value' ) ) {
                $table->float( 'tax_value' )->default(0);
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
        //
    }
};
