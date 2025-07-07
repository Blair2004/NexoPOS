<?php

use App\Classes\Schema;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Artisan::call( 'ns:doctor', [
            '--purge-orphan-migrations' => true,
        ]);

        Schema::table( 'nexopos_notifications', function ( Blueprint $table ) {
            if ( ! Schema::hasColumn( 'nexopos_notifications', 'actions' ) ) {
                $table->json( 'actions' )->nullable();
            }
        } );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table( 'nexopos_notifications', function ( Blueprint $table ) {
            $table->dropColumn( 'actions' );
        } );
    }
};
