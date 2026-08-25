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
        if ( ! Schema::hasTable( 'nexopos_users_widgets' ) || Schema::hasColumn( 'nexopos_users_widgets', 'layout' ) ) {
            return;
        }

        Schema::table( 'nexopos_users_widgets', function ( Blueprint $table ) {
            $table->string( 'layout', 3 )->nullable()->after( 'position' );
        } );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if ( ! Schema::hasTable( 'nexopos_users_widgets' ) || ! Schema::hasColumn( 'nexopos_users_widgets', 'layout' ) ) {
            return;
        }

        Schema::table( 'nexopos_users_widgets', function ( Blueprint $table ) {
            $table->dropColumn( 'layout' );
        } );
    }
};
