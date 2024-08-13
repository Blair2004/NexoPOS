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
        Schema::table( 'nexopos_transactions_accounts', function ( Blueprint $table ) {
            if ( ! Schema::hasColumn( 'nexopos_transactions_accounts', 'counter_account_id' ) ) {
                $table->integer( 'counter_account_id' )->default( 0 )->nullable();
            }
            if ( ! Schema::hasColumn( 'nexopos_transactions_accounts', 'category_identifier' ) ) {
                $table->string( 'category_identifier' )->nullable();
            }
        } );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table( 'nexopos_transactions_accounts', function ( Blueprint $table ) {
            if ( Schema::hasColumn( 'nexopos_transactions_accounts', 'counter_account_id' ) ) {
                $table->dropColumn( 'counter_account_id' );
            }

            if ( Schema::hasColumn( 'nexopos_transactions_accounts', 'category_identifier' ) ) {
                $table->dropColumn( 'category_identifier' );
            }
        } );
    }
};
