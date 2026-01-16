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
        Schema::table( 'nexopos_products', function ( Blueprint $table ) {
            if ( Schema::hasColumn( 'nexopos_products', 'scale_barcode_preferred_unit_id' ) ) {
                $table->dropColumn( 'scale_barcode_preferred_unit_id' );
            }
        } );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table( 'nexopos_products', function ( Blueprint $table ) {
            if ( ! Schema::hasColumn( 'nexopos_products', 'scale_barcode_preferred_unit_id' ) ) {
                $table->integer( 'scale_barcode_preferred_unit_id' )->nullable()->after( 'unit_group' );
            }
        } );
    }
};
