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
        if (Schema::hasTable('nexopos_expenses')) {
            Schema::rename('nexopos_expenses', 'nexopos_transactions');
        }

        if (Schema::hasTable('nexopos_expense_categories')) {
            Schema::rename('nexopos_expense_categories', 'nexopos_transactions_accounts');
        }
        
        if (Schema::hasTable('nexopos_cash_flow')) {
            Schema::rename('nexopos_cash_flow', 'nexopos_transactions_histories');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
