<?php

use App\Classes\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        Schema::createIfMissing( 'nexopos_oxen_settings', function ( Blueprint $table ): void {
            $table->id();
            $table->string( 'provider' )->default( 'openai' );
            $table->text( 'api_key' )->nullable();
            $table->string( 'model' )->default( 'gpt-5-mini' );
            $table->string( 'image_model' )->default( 'gpt-image-2' );
            $table->boolean( 'assistant_enabled' )->default( false );
            $table->boolean( 'writes_enabled' )->default( false );
            $table->unsignedInteger( 'daily_message_limit' )->default( 100 );
            $table->unsignedInteger( 'output_token_limit' )->default( 2000 );
            $table->unsignedInteger( 'context_token_limit' )->default( 12000 );
            $table->unsignedInteger( 'tool_call_limit' )->default( 8 );
            $table->timestamps();
        } );
        Schema::createIfMissing( 'nexopos_oxen_conversations', function ( Blueprint $table ): void {
            $table->id();
            $table->uuid( 'public_id' )->unique();
            $table->unsignedBigInteger( 'user_id' )->index();
            $table->string( 'title', 160 );
            $table->string( 'provider', 40 );
            $table->string( 'model', 120 );
            $table->string( 'status', 30 )->default( 'active' );
            $table->text( 'summary' )->nullable();
            $table->timestamp( 'last_activity_at' )->index();
            $table->timestamps();
            $table->index( [ 'user_id', 'last_activity_at' ], 'oxen_conversations_owner_activity' );
        } );
        Schema::createIfMissing( 'nexopos_oxen_messages', function ( Blueprint $table ): void {
            $table->id();
            $table->unsignedBigInteger( 'conversation_id' )->index();
            $table->string( 'role', 20 );
            $table->longText( 'content' )->nullable();
            $table->json( 'metadata' )->nullable();
            $table->string( 'status', 30 )->default( 'completed' );
            $table->unsignedInteger( 'input_tokens' )->default( 0 );
            $table->unsignedInteger( 'output_tokens' )->default( 0 );
            $table->timestamps();
        } );
        Schema::createIfMissing( 'nexopos_oxen_operations', function ( Blueprint $table ): void {
            $table->id();
            $table->uuid( 'correlation_id' )->index();
            $table->unsignedBigInteger( 'user_id' )->index();
            $table->unsignedBigInteger( 'token_id' )->nullable()->index();
            $table->unsignedBigInteger( 'store_id' )->nullable()->index();
            $table->string( 'tool', 100 )->index();
            $table->json( 'redacted_input' )->nullable();
            $table->char( 'input_hash', 64 );
            $table->string( 'result_reference' )->nullable();
            $table->string( 'status', 30 )->index();
            $table->unsignedInteger( 'duration_ms' )->default( 0 );
            $table->timestamps();
        } );
        Schema::createIfMissing( 'nexopos_oxen_idempotency', function ( Blueprint $table ): void {
            $table->id();
            $table->unsignedBigInteger( 'user_id' );
            $table->string( 'tool', 100 );
            $table->string( 'idempotency_key', 100 );
            $table->char( 'input_hash', 64 );
            $table->json( 'result' );
            $table->timestamp( 'expires_at' )->index();
            $table->timestamps();
            $table->unique( [ 'user_id', 'tool', 'idempotency_key' ], 'oxen_idempotency_unique' );
        } );
        Schema::createIfMissing( 'nexopos_oxen_usage', function ( Blueprint $table ): void {
            $table->id();
            $table->unsignedBigInteger( 'user_id' );
            $table->date( 'usage_date' );
            $table->unsignedInteger( 'messages' )->default( 0 );
            $table->unsignedBigInteger( 'input_tokens' )->default( 0 );
            $table->unsignedBigInteger( 'output_tokens' )->default( 0 );
            $table->timestamps();
            $table->unique( [ 'user_id', 'usage_date' ], 'oxen_usage_user_date' );
        } );
    }

    public function down(): void
    {
        foreach ( [ 'nexopos_oxen_usage', 'nexopos_oxen_idempotency', 'nexopos_oxen_operations', 'nexopos_oxen_messages', 'nexopos_oxen_conversations', 'nexopos_oxen_settings' ] as $table ) {
            Schema::dropIfExists( $table );
        }
    }
};
