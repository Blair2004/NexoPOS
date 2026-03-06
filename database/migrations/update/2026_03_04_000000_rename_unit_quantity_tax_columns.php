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
        $table = 'nexopos_products_unit_quantities';

        if ( Schema::hasTable( $table ) ) {
            Schema::table( $table, function ( Blueprint $table ) {
                if ( Schema::hasColumn( 'nexopos_products_unit_quantities', 'sale_price_with_tax' ) ) {
                    $table->renameColumn( 'sale_price_with_tax', 'sale_price_gross' );
                }
                if ( Schema::hasColumn( 'nexopos_products_unit_quantities', 'sale_price_without_tax' ) ) {
                    $table->renameColumn( 'sale_price_without_tax', 'sale_price_net' );
                }
                if ( Schema::hasColumn( 'nexopos_products_unit_quantities', 'wholesale_price_with_tax' ) ) {
                    $table->renameColumn( 'wholesale_price_with_tax', 'wholesale_price_gross' );
                }
                if ( Schema::hasColumn( 'nexopos_products_unit_quantities', 'wholesale_price_without_tax' ) ) {
                    $table->renameColumn( 'wholesale_price_without_tax', 'wholesale_price_net' );
                }
                if ( Schema::hasColumn( 'nexopos_products_unit_quantities', 'custom_price_with_tax' ) ) {
                    $table->renameColumn( 'custom_price_with_tax', 'custom_price_gross' );
                }
                if ( Schema::hasColumn( 'nexopos_products_unit_quantities', 'custom_price_without_tax' ) ) {
                    $table->renameColumn( 'custom_price_without_tax', 'custom_price_net' );
                }
            } );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $table = 'nexopos_products_unit_quantities';

        if ( Schema::hasTable( $table ) ) {
            Schema::table( $table, function ( Blueprint $table ) {
                if ( Schema::hasColumn( 'nexopos_products_unit_quantities', 'sale_price_gross' ) ) {
                    $table->renameColumn( 'sale_price_gross', 'sale_price_with_tax' );
                }
                if ( Schema::hasColumn( 'nexopos_products_unit_quantities', 'sale_price_net' ) ) {
                    $table->renameColumn( 'sale_price_net', 'sale_price_without_tax' );
                }
                if ( Schema::hasColumn( 'nexopos_products_unit_quantities', 'wholesale_price_gross' ) ) {
                    $table->renameColumn( 'wholesale_price_gross', 'wholesale_price_with_tax' );
                }
                if ( Schema::hasColumn( 'nexopos_products_unit_quantities', 'wholesale_price_net' ) ) {
                    $table->renameColumn( 'wholesale_price_net', 'wholesale_price_without_tax' );
                }
                if ( Schema::hasColumn( 'nexopos_products_unit_quantities', 'custom_price_gross' ) ) {
                    $table->renameColumn( 'custom_price_gross', 'custom_price_with_tax' );
                }
                if ( Schema::hasColumn( 'nexopos_products_unit_quantities', 'custom_price_net' ) ) {
                    $table->renameColumn( 'custom_price_net', 'custom_price_without_tax' );
                }
            } );
        }
    }
};
