<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('nexopos_transactions_accounts', function (Blueprint $table) {
            // a transaction account is no longer forced to be "credit" or "debit"
            if ( Schema::hasColumn( 'nexopos_transactions_accounts', 'operation' ) ) {
                $table->dropColumn( 'operation' );
            }
            if ( Schema::hasColumn( 'nexopos_transactions_accounts', 'account' ) ) {
                $table->dropColumn( 'account' );
            }
            if ( ! Schema::hasColumn( 'nexopos_transactions_accounts', 'type' ) ) {
                $table->enum( 'type', [ 'asset', 'liability', 'equity', 'revenue', 'expense', 'inventory' ] )->default( 'asset' );
            }
        });

        Schema::table( 'nexopos_transactions', function( Blueprint $table ) {
            if ( ! Schema::hasColumn( 'nexopos_transactions', 'nature' ) ) {
                $table->enum( 'nature', [ 'credit', 'debit' ] )->default( 'credit' );
            }
            if ( Schema::hasColumn( 'nexopos_transactions', 'active' ) ) {
                $table->renameColumn( 'active', 'is_active' );
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nexopos_transactions_accounts', function (Blueprint $table) {
            $table->string( 'operation' )->default('credit');
            $table->string( 'account' )->nullable();
            $table->renameColumn( 'type', 'account' );
        });

        Schema::table( 'nexopos_transactions', function( Blueprint $table ) {
            $table->dropColumn( 'nature' );
            $table->renameColumn( 'is_active', 'active' );
        });
    }
};
