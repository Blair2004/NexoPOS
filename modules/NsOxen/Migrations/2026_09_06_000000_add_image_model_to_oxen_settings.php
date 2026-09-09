<?php

use App\Classes\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        if ( Schema::hasTable( 'nexopos_oxen_settings' ) && ! Schema::hasColumn( 'nexopos_oxen_settings', 'image_model' ) ) {
            Schema::table( 'nexopos_oxen_settings', function ( Blueprint $table ): void {
                $table->string( 'image_model' )->default( 'gpt-image-2' )->after( 'model' );
            } );
        }
    }

    public function down(): void
    {
        if ( Schema::hasTable( 'nexopos_oxen_settings' ) && Schema::hasColumn( 'nexopos_oxen_settings', 'image_model' ) ) {
            Schema::table( 'nexopos_oxen_settings', function ( Blueprint $table ): void {
                $table->dropColumn( 'image_model' );
            } );
        }
    }
};
