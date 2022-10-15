<?php

use App\Classes\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class UpdateNexoPOSOrdersPayments220126 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table( 'nexopos_payments_types', function( Blueprint $table ) {
            if ( ! Schema::hasColumn( 'nexopos_payments_types', 'priority' ) ) {
                $table->integer( 'priority' )->default(0);
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
        Schema::table( 'nexopos_payments_types', function( Blueprint $table ) {
            if ( Schema::hasColumn( 'nexopos_payments_types', 'priority' ) ) {
                $table->dropColumn( 'priority' );
            }
        });
    }
}
