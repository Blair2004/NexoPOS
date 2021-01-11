<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use App\Classes\Schema;

class UpdateOrdersTableOct8 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table( 'nexopos_orders', function( Blueprint $table ){
            if ( ! Schema::hasColumn( 'nexopos_orders', 'subtotal' ) ) {
                $table->float( 'subtotal' )->default(0);
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
        Schema::table( 'nexopos_orders', function( Blueprint $table ){
            if ( Schema::hasColumn( 'nexopos_orders', 'subtotal' ) ) {
                $table->dropColumn( 'subtotal' );
            }
        });
    }
}
