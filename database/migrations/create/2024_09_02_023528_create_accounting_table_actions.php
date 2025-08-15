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
            $table->timestamps();
        } );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists( 'nexopos_transactions_actions_rules' );
    }
};
