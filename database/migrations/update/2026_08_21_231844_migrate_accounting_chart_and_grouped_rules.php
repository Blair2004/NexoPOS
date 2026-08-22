<?php

use App\Classes\Schema;
use App\Services\TransactionService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        if (
            Schema::hasTable( 'nexopos_transactions_accounts' )
            && Schema::hasTable( 'nexopos_transactions_actions_rules' )
            && Schema::hasTable( 'nexopos_transactions_actions_rule_lines' )
        ) {
            app( TransactionService::class )->upgradeAccountingFoundation();
        }
    }

    public function down(): void
    {
        // Existing accounts, rules, and journals are intentionally preserved.
    }
};
