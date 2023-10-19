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
        if ( Schema::hasTable( 'nexopos_orders_products' ) ) {
            Schema::table( 'nexopos_orders_products', function ( Blueprint $table ) {
                if ( ! Schema::hasColumn( 'nexopos_orders_products', 'total_purchase_price' ) ) {
                    $table->float( 'total_purchase_price' )->default(0);
                }
            });
        }
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
