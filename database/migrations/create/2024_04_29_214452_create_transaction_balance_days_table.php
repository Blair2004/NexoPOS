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
        Schema::createIfMissing( 'nexopos_transactions_balance_days', function ( Blueprint $table ) {
            $table->id();
            $table->decimal( 'opening_balance', 18, 5 )->default( 0 );
            $table->decimal( 'income', 18, 5 )->default( 0 );
            $table->decimal( 'expense', 18, 5 )->default( 0 );
            $table->decimal( 'closing_balance', 18, 5 )->default( 0 );
            $table->date( 'date' )->nullable();
            $table->timestamps();
        } );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists( 'nexopos_transactions_balance_days' );
    }
};
