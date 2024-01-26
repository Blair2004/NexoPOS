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
        Schema::table( 'nexopos_transactions_histories', function ( Blueprint $table ) {
            if ( Schema::hasColumn( 'nexopos_transactions_histories', 'expense_category_id' ) ) {
                $table->renameColumn( 'expense_category_id', 'transaction_account_id' );
            }
        });

        Schema::table( 'nexopos_procurements_products', function ( Blueprint $table ) {
            if ( Schema::hasColumn( 'nexopos_procurements_products', 'tax_group_id' ) ) {
                $table->unsignedBigInteger('tax_group_id')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
