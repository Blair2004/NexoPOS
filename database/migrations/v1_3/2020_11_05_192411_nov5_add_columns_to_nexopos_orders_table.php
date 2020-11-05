<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Nov5AddColumnsToNexoposOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('nexopos_orders', function (Blueprint $table) {
            if ( ! Schema::hasColumn( 'nexopos_orders', 'expected_payment_date' ) ) {
                $table->datetime( 'expected_payment_date' )->nullable();
            }
            if ( ! Schema::hasColumn( 'nexopos_orders', 'total_installments' ) ) {
                $table->integer( 'total_installments' )->nullable();
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
        Schema::table('nexopos_orders', function (Blueprint $table) {
            if ( Schema::hasColumn( 'nexopos_orders', 'expected_payment_date' ) ) {
                $table->dropColumn( 'expected_payment_date' );
            }

            if ( Schema::hasColumn( 'nexopos_orders', 'total_installments' ) ) {
                $table->dropColumn( 'total_installments' );
            }
        });
    }
}
