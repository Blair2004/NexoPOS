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
        if ( ! Schema::hasTable( 'nexopos_oxen_action_proposals' )
            || ! Schema::hasColumn( 'nexopos_oxen_action_proposals', 'expires_at' ) ) {
            return;
        }

        Schema::table( 'nexopos_oxen_action_proposals', function ( Blueprint $table ): void {
            $table->dateTime( 'expires_at' )->change();
        } );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if ( ! Schema::hasTable( 'nexopos_oxen_action_proposals' )
            || ! Schema::hasColumn( 'nexopos_oxen_action_proposals', 'expires_at' ) ) {
            return;
        }

        Schema::table( 'nexopos_oxen_action_proposals', function ( Blueprint $table ): void {
            $table->timestamp( 'expires_at' )->change();
        } );
    }
};
