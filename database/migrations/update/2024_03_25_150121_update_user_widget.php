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
        Schema::table( 'users_widgets', function ( Blueprint $table ) {
            if ( Schema::hasColumn( 'users_widgets', 'id' ) ) {
                $table->dropColumn( 'id' );
            }
        } );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
