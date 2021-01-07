<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use App\Classes\Schema;

class UpdateNexoposOrdersPaymentsTableOct14 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table( 'nexopos_orders_payments', function( Blueprint $table ) {
            if ( Schema::hasColumn( 'nexopos_orders_payments', 'namespace' ) ) {
                $table->renameColumn( 'namespace', 'identifier' );
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
        if ( Schema::hasTable( 'nexopos_orders_payments' ) ) {
            Schema::table( 'nexopos_orders_payments', function( Blueprint $table ) {
                if ( Schema::hasColumn( 'nexopos_orders_payments', 'identifier' ) ) {
                    $table->renameColumn( 'identifier', 'namespace' );
                }
            });
        }
    }
}
