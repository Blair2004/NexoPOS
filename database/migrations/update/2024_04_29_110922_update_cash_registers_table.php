<?php

use App\Classes\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if ( Schema::hasTable( 'nexopos_registers_history' ) ) {
            Schema::table( 'nexopos_registers_history', function ( Blueprint $table ) {
                if ( ! Schema::hasColumn( 'nexopos_registers_history', 'transaction_account_id' ) ) {
                    $table->integer( 'transaction_account_id' )->nullable();
                }
            } );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if ( Schema::hasTable( 'nexopos_registers_history' ) ) {
            Schema::table( 'nexopos_registers_history', function ( Blueprint $table ) {
                $table->dropColumn( 'transaction_account_id' );
            } );
        }
    }
};
