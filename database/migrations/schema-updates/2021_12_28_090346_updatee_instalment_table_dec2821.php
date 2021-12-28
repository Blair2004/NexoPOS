<?php

use App\Classes\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class UpdateeInstalmentTableDec2821 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table( 'nexopos_orders_instalments', function( Blueprint $table ) {
            if ( ! Schema::hasColumn( 'nexopos_orders_instalments', 'payment_id' ) ) {
                $table->integer( 'payment_id' )->nullable();
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
        Schema::table( 'nexopos_orders_instalments', function( Blueprint $table ) {
            if ( Schema::hasColumn( 'nexopos_orders_instalments', 'payment_id' ) ) {
                $table->dropColumn( 'payment_id' );
            }
        });
    }
}
