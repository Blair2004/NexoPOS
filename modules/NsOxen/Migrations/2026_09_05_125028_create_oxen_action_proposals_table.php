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
        Schema::createIfMissing( 'nexopos_oxen_action_proposals', function ( Blueprint $table ): void {
            $table->id();
            $table->uuid( 'public_id' )->unique();
            $table->unsignedBigInteger( 'user_id' );
            $table->unsignedBigInteger( 'store_id' )->nullable();
            $table->unsignedBigInteger( 'conversation_id' )->nullable();
            $table->unsignedBigInteger( 'message_id' )->nullable();
            $table->string( 'tool', 100 );
            $table->longText( 'payload' );
            $table->char( 'input_hash', 64 );
            $table->string( 'risk', 20 );
            $table->boolean( 'requires_confirmation' )->default( false );
            $table->string( 'status', 30 )->default( 'pending' );
            $table->timestamp( 'expires_at' );
            $table->uuid( 'idempotency_key' );
            $table->timestamp( 'executed_at' )->nullable();
            $table->timestamp( 'rejected_at' )->nullable();
            $table->string( 'result_reference' )->nullable();
            $table->timestamps();
            $table->index( ['user_id', 'status', 'expires_at'], 'oxen_proposals_owner_status_expiry' );
            $table->unique( ['user_id', 'tool', 'idempotency_key'], 'oxen_proposals_idempotency_unique' );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if ( Schema::hasTable( 'nexopos_oxen_action_proposals' ) ) {
            Schema::dropIfExists( 'nexopos_oxen_action_proposals' );
        }
    }
};
