<?php

use App\Classes\Schema;
use App\Models\Transaction;
use App\Models\TransactionHistory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if ( Schema::hasTable( 'nexopos_transactions_histories' ) ) {
            Schema::table( 'nexopos_transactions_histories', function ( Blueprint $table ) {
                if ( ! Schema::hasColumn( 'nexopos_transactions_histories', 'type' ) ) {
                    $table->string( 'type' )->nullable();
                }
                if ( ! Schema::hasColumn( 'nexopos_transactions_histories', 'trigger_date' ) ) {
                    $table->datetime( 'trigger_date' )->nullable();
                }
                if ( Schema::hasColumn( 'nexopos_transactions_histories', 'status' ) ) {
                    $table->string( 'status' )->default( TransactionHistory::STATUS_PENDING )->change();
                }
            } );

            TransactionHistory::query()->update( [
                'trigger_date' => ns()->date->toDateTimeString(),
                'status' => TransactionHistory::STATUS_ACTIVE,
                'type' => Transaction::TYPE_DIRECT,
            ] );
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
