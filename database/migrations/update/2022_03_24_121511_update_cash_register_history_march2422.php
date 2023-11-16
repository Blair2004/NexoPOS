<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table( 'nexopos_registers_history', function ( Blueprint $table ) {
            if ( ! Schema::hasColumn( 'nexopos_registers_history', 'balance_before' ) ) {
                $table->float( 'balance_before' )->default(0);
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
        Schema::table( 'nexopos_registers_history', function ( Blueprint $table ) {
            if ( Schema::hasColumn( 'nexopos_registers_history', 'balance_before' ) ) {
                $table->dropColumn( 'balance_before' );
            }
        });
    }
};
