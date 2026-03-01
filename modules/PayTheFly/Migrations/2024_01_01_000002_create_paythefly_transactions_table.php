<?php

use App\Classes\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    /**
     * Create a dedicated table for PayTheFly transaction records.
     * This provides a full audit trail separate from the order notes.
     */
    public function up(): void
    {
        Schema::createIfMissing( 'nexopos_paythefly_transactions', function ( Blueprint $table ) {
            $table->id();
            $table->unsignedBigInteger( 'order_id' )->index();
            $table->string( 'serial_no' )->index();
            $table->string( 'tx_hash' )->nullable()->index();
            $table->string( 'chain_symbol', 10 );
            $table->string( 'wallet' )->nullable();
            $table->string( 'value' );
            $table->string( 'fee' )->nullable();
            $table->tinyInteger( 'tx_type' )->comment( '1=payment, 2=withdrawal' );
            $table->boolean( 'confirmed' )->default( false );
            $table->string( 'project_id' );
            $table->text( 'raw_payload' )->nullable();
            $table->timestamps();

            $table->foreign( 'order_id' )
                  ->references( 'id' )
                  ->on( 'nexopos_orders' )
                  ->onDelete( 'cascade' );
        });
    }

    /**
     * Drop the PayTheFly transactions table.
     */
    public function down(): void
    {
        Schema::dropIfExists( 'nexopos_paythefly_transactions' );
    }
};
