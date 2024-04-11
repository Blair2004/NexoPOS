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
        Schema::table( 'nexopos_transactions_histories', function ( Blueprint $table ) {
            if ( Schema::hasColumn( 'nexopos_transactions_histories', 'expense_category_id' ) ) {
                $table->integer( 'expense_category_id' )->nullable()->default( 0 )->change();
            }
            if ( Schema::hasColumn( 'nexopos_transactions_histories', 'expense_category_id' ) ) {
                $table->renameColumn( 'expense_category_id', 'transaction_account_id' );
            }

            if ( Schema::hasColumn( 'nexopos_transactions_histories', 'expense_id' ) ) {
                $table->integer( 'expense_id' )->nullable()->default( 0 )->change();
            }
            if ( Schema::hasColumn( 'nexopos_transactions_histories', 'expense_id' ) ) {
                $table->renameColumn( 'expense_id', 'transaction_id' );
            }

            if ( ! Schema::hasColumn( 'nexopos_transactions_histories', 'order_product_id' ) ) {
                $table->integer( 'order_product_id' )->nullable();
            }
            if ( ! Schema::hasColumn( 'nexopos_transactions_histories', 'order_refund_product_id' ) ) {
                $table->integer( 'order_refund_product_id' )->nullable();
            }
        } );

        Schema::table( 'nexopos_procurements_products', function ( Blueprint $table ) {
            if ( Schema::hasColumn( 'nexopos_procurements_products', 'tax_group_id' ) ) {
                $table->unsignedBigInteger( 'tax_group_id' )->nullable()->change();
            }
        } );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
