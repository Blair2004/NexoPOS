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
        Schema::createIfMissing( 'nexopos_transactions_actions_rules', function ( Blueprint $table ) {
            $table->id();
            $table->string( 'on' );
            $table->enum( 'action', [ 'increase', 'decrease' ] );
            $table->integer( 'account_id' );
            $table->enum( 'do', [ 'increase', 'decrease'] );
            $table->integer( 'offset_account_id' );
            $table->boolean( 'locked' )->default( false );
            $table->boolean( 'active' )->default( true );
            $table->timestamps();
        } );

        Schema::createIfMissing( 'nexopos_transactions_actions_rule_lines', function ( Blueprint $table ) {
            $table->id();
            $table->unsignedBigInteger( 'rule_id' );
            $table->unsignedBigInteger( 'account_id' )->nullable();
            $table->string( 'dynamic_account_role' )->nullable();
            $table->enum( 'effect', [ 'increase', 'decrease' ] );
            $table->string( 'amount_source' );
            $table->unsignedInteger( 'display_order' )->default( 0 );
            $table->timestamps();
            $table->index( [ 'rule_id', 'display_order' ], 'transaction_rule_lines_order_index' );
        } );

        Schema::createIfMissing( 'nexopos_accounting_journals', function ( Blueprint $table ) {
            $table->id();
            $table->string( 'source_type' );
            $table->string( 'source_id' );
            $table->string( 'event' );
            $table->unsignedBigInteger( 'rule_id' )->nullable();
            $table->string( 'name' );
            $table->enum( 'status', [ 'posted', 'reversed' ] )->default( 'posted' );
            $table->integer( 'author_id' );
            $table->datetime( 'posted_at' );
            $table->timestamps();
            $table->unique( [ 'source_type', 'source_id', 'event' ], 'accounting_journals_source_event_unique' );
        } );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists( 'nexopos_transactions_actions_rules' );
        Schema::dropIfExists( 'nexopos_transactions_actions_rule_lines' );
        Schema::dropIfExists( 'nexopos_accounting_journals' );
    }
};
