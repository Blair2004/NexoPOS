<?php

use App\Classes\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class UpdateCashRegisterHistoryMarch12022 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if ( Schema::hasTable( 'nexopos_registers_history' ) ) {
            Schema::table( 'nexopos_registers_history', function( Blueprint $table ) {
                if ( ! Schema::hasColumn( 'nexopos_registers_history', 'diff_type' ) ) {
                    $table->string( 'diff_type' )->nullable(); // can be "negative", "positive".
                }

                if ( ! Schema::hasColumn( 'nexopos_registers_history', 'diff_type' ) ) {
                    $table->float( 'diff_amount' )->default(0);
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
        if ( Schema::hasTable( 'nexopos_registers_history' ) ) {
            Schema::table( 'nexopos_registers_history', function( Blueprint $table ) {
                if ( Schema::hasColumn( 'nexopos_registers_history', 'diff_type' ) ) {
                    $table->dropColumn( 'diff_type' );
                }

                if ( Schema::hasColumn( 'nexopos_registers_history', 'diff_type' ) ) {
                    $table->dropColumn( 'diff_amount' );
                }
            });
        }
    }
}
