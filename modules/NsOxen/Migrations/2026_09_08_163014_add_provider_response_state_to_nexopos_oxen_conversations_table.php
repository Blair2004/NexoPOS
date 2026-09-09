<?php

use App\Classes\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        if ( ! Schema::hasTable( 'nexopos_oxen_conversations' ) ) {
            return;
        }

        if ( ! Schema::hasColumn( 'nexopos_oxen_conversations', 'provider_response_id' ) ) {
            Schema::table( 'nexopos_oxen_conversations', function ( Blueprint $table ): void {
                $table->string( 'provider_response_id' )->nullable()->after( 'summary' );
            } );
        }

        if ( ! Schema::hasColumn( 'nexopos_oxen_conversations', 'provider_response_message_id' ) ) {
            Schema::table( 'nexopos_oxen_conversations', function ( Blueprint $table ): void {
                $table->unsignedBigInteger( 'provider_response_message_id' )->nullable()->after( 'provider_response_id' );
            } );
        }
    }

    public function down(): void
    {
        if ( ! Schema::hasTable( 'nexopos_oxen_conversations' ) ) {
            return;
        }

        if ( Schema::hasColumn( 'nexopos_oxen_conversations', 'provider_response_message_id' ) ) {
            Schema::table( 'nexopos_oxen_conversations', function ( Blueprint $table ): void {
                $table->dropColumn( 'provider_response_message_id' );
            } );
        }

        if ( Schema::hasColumn( 'nexopos_oxen_conversations', 'provider_response_id' ) ) {
            Schema::table( 'nexopos_oxen_conversations', function ( Blueprint $table ): void {
                $table->dropColumn( 'provider_response_id' );
            } );
        }
    }
};
