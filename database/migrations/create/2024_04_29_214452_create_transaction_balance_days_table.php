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
        Schema::create('nexopos_transactions_balance_days', function (Blueprint $table) {
            $table->id();
            $table->float( 'opening_balance' )->default(0);
            $table->float( 'income' )->default(0);
            $table->float( 'expense' )->default(0);
            $table->float( 'closing_balance' )->default(0);
            $table->date( 'date' )->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nexopos_transactions_balance_days');
    }
};
