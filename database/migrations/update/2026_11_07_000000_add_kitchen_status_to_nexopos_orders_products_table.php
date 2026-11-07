<?php

/**
 * KDS (Kitchen Display System) — MVP
 *
 * Adds a per-item kitchen status on nexopos_orders_products so the kitchen
 * screen can mark each dish as pending / preparing / done without touching
 * the existing `status` column (sold / refunded), which drives refunds.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if ( Schema::hasTable( 'nexopos_orders_products' ) && ! Schema::hasColumn( 'nexopos_orders_products', 'kitchen_status' ) ) {
            Schema::table( 'nexopos_orders_products', function ( Blueprint $table ) {
                $table->string( 'kitchen_status' )->default( 'pending' )->after( 'status' );
            } );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if ( Schema::hasTable( 'nexopos_orders_products' ) && Schema::hasColumn( 'nexopos_orders_products', 'kitchen_status' ) ) {
            Schema::table( 'nexopos_orders_products', function ( Blueprint $table ) {
                $table->dropColumn( 'kitchen_status' );
            } );
        }
    }
};
