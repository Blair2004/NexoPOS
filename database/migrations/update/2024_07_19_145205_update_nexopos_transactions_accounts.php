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
            if ( ! Schema::hasColumn( 'nexopos_transactions_accounts', 'counter_increase_account_id' ) ) {
                $table->integer( 'counter_increase_account_id' )->default( 0 )->nullable();
            }
            if ( ! Schema::hasColumn( 'nexopos_transactions_accounts', 'counter_decrease_account_id' ) ) {
                $table->integer( 'counter_decrease_account_id' )->default( 0 )->nullable();
            }
            if ( ! Schema::hasColumn( 'nexopos_transactions_accounts', 'category_identifier' ) ) {
                $table->string( 'category_identifier' )->nullable();
            }
            if ( Schema::hasColumn( 'nexopos_transactions_accounts', 'operation' ) ) {
                $table->dropColumn( 'operation' );
            }

            if ( ! Schema::hasColumn( 'nexopos_transactions_accounts', 'sub_category_id' ) ) {
                $table->integer( 'sub_category_id' )->nullable();
            }
        } );

        Schema::table( 'nexopos_transactions_histories', function ( Blueprint $table ) {
            if ( ! Schema::hasColumn( 'nexopos_transactions_histories', 'is_reflection' ) ) {
                $table->boolean( 'is_reflection' )->default( false );
            }
            if ( ! Schema::hasColumn( 'nexopos_transactions_histories', 'reflection_source_id' ) ) {
                $table->integer( 'reflection_source_id' )->nullable();
            }
        } );

        Schema::table( 'nexopos_orders', function ( Blueprint $table ) {
            if ( ! Schema::hasColumn( 'nexopos_orders', 'total_cogs' ) ) {
                $table->float( 'total_cogs', 18, 5 )->nullable();
            }
        } );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table( 'nexopos_transactions_accounts', function ( Blueprint $table ) {

            if ( Schema::hasColumn( 'nexopos_transactions_accounts', 'category_identifier' ) ) {
                $table->dropColumn( 'category_identifier' );
            }

            if ( ! Schema::hasColumn( 'nexopos_transactions_accounts', 'operation' ) ) {
                $table->string( 'operation' )->default( 'debit' );
            }

            if ( Schema::hasColumn( 'nexopos_transactions_accounts', 'sub_category_id' ) ) {
                $table->dropColumn( 'sub_category_id' );
            }
        } );

        Schema::table( 'nexopos_transactions_histories', function ( Blueprint $table ) {
            if ( Schema::hasColumn( 'nexopos_transactions_histories', 'is_reflection' ) ) {
                $table->dropColumn( 'is_reflection' );
            }
            if ( Schema::hasColumn( 'nexopos_transactions_histories', 'reflection_source_id' ) ) {
                $table->dropColumn( 'reflection_source_id' );
            }
        } );

        Schema::table( 'nexopos_orders', function ( Blueprint $table ) {
            if ( Schema::hasColumn( 'nexopos_orders', 'total_cogs' ) ) {
                $table->dropColumn( 'total_cogs' );
            }
        } );
    }
};
