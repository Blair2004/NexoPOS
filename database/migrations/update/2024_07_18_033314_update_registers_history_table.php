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
        Schema::table( 'nexopos_registers_history', function ( Blueprint $table ) {
            if ( ! Schema::hasColumn( 'nexopos_registers_history', 'transaction_account_id' ) ) {
                $table->integer( 'transaction_account_id' )->nullable()->after( 'payment_id' );
            }
        } );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table( 'nexopos_registers_history', function ( Blueprint $table ) {
            if ( Schema::hasColumn( 'nexopos_registers_history', 'transaction_account_id' ) ) {
                $table->dropColumn( 'transaction_account_id' );
            }
        } );
    }
};
