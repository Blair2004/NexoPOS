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
        Schema::table( 'nexopos_products_unit_quantities', function ( Blueprint $table ) {
            if ( ! Schema::hasColumn( 'nexopos_products_unit_quantities', 'scale_plu' ) ) {
                $table->string( 'scale_plu', 10 )->nullable()->unique()->after( 'barcode' );
            }
            
            if ( ! Schema::hasColumn( 'nexopos_products_unit_quantities', 'is_weighable' ) ) {
                $table->boolean( 'is_weighable' )->default( false )->after( 'scale_plu' );
            }
        } );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table( 'nexopos_products_unit_quantities', function ( Blueprint $table ) {
            if ( Schema::hasColumn( 'nexopos_products_unit_quantities', 'is_weighable' ) ) {
                $table->dropColumn( 'is_weighable' );
            }
            
            if ( Schema::hasColumn( 'nexopos_products_unit_quantities', 'scale_plu' ) ) {
                $table->dropColumn( 'scale_plu' );
            }
        } );
    }
};
