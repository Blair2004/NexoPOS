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
        if ( Schema::hasTable( 'nexopos_registers_history' ) ) {
            Schema::table( 'nexopos_registers_history', function ( Blueprint $table ) {
                if ( ! Schema::hasColumn( 'nexopos_registers_history', 'transaction_type' ) ) {
                    $table->string( 'transaction_type' )->nullable(); // can be "negative", "positive".
                }

                if ( ! Schema::hasColumn( 'nexopos_registers_history', 'balance_after' ) ) {
                    $table->float( 'balance_after' )->default(0);
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
            Schema::table( 'nexopos_registers_history', function ( Blueprint $table ) {
                if ( Schema::hasColumn( 'nexopos_registers_history', 'transaction_type' ) ) {
                    $table->dropColumn( 'transaction_type' );
                }

                if ( Schema::hasColumn( 'nexopos_registers_history', 'balance_after' ) ) {
                    $table->dropColumn( 'balance_after' );
                }
            });
        }
    }
};
