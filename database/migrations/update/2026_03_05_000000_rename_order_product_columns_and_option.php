<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        /**
         * Rename columns on nexopos_orders_products:
         * price_with_tax        → price_gross
         * price_without_tax     → price_net
         * total_price_with_tax  → total_price_gross
         * total_price_without_tax → total_price_net
         */
        if ( Schema::hasTable( 'nexopos_orders_products' ) ) {
            Schema::table( 'nexopos_orders_products', function ( Blueprint $table ) {
                if ( Schema::hasColumn( 'nexopos_orders_products', 'price_with_tax' ) ) {
                    $table->renameColumn( 'price_with_tax', 'price_gross' );
                }
                if ( Schema::hasColumn( 'nexopos_orders_products', 'price_without_tax' ) ) {
                    $table->renameColumn( 'price_without_tax', 'price_net' );
                }
                if ( Schema::hasColumn( 'nexopos_orders_products', 'total_price_with_tax' ) ) {
                    $table->renameColumn( 'total_price_with_tax', 'total_price_gross' );
                }
                if ( Schema::hasColumn( 'nexopos_orders_products', 'total_price_without_tax' ) ) {
                    $table->renameColumn( 'total_price_without_tax', 'total_price_net' );
                }
            } );
        }

        /**
         * Rename option key ns_pos_price_with_tax → ns_pos_prefered_price
         * and map values: yes → gross_prices, no → net_prices
         */
        if ( Schema::hasTable( 'nexopos_options' ) ) {
            DB::table( 'nexopos_options' )
                ->where( 'key', 'ns_pos_price_with_tax' )
                ->update( [
                    'key' => 'ns_pos_prefered_price',
                    'value' => DB::raw( "CASE WHEN value = 'yes' THEN 'gross_prices' WHEN value = 'no' THEN 'net_prices' ELSE 'net_prices' END" ),
                ] );
        }

        /**
         * Also update any stored order settings that reference the old key
         */
        if ( Schema::hasTable( 'nexopos_orders_settings' ) ) {
            DB::table( 'nexopos_orders_settings' )
                ->where( 'key', 'ns_pos_price_with_tax' )
                ->update( [
                    'key' => 'ns_pos_prefered_price',
                    'value' => DB::raw( "CASE WHEN value = 'yes' THEN 'gross_prices' WHEN value = 'no' THEN 'net_prices' ELSE 'net_prices' END" ),
                ] );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if ( Schema::hasTable( 'nexopos_orders_products' ) ) {
            Schema::table( 'nexopos_orders_products', function ( Blueprint $table ) {
                if ( Schema::hasColumn( 'nexopos_orders_products', 'price_gross' ) ) {
                    $table->renameColumn( 'price_gross', 'price_with_tax' );
                }
                if ( Schema::hasColumn( 'nexopos_orders_products', 'price_net' ) ) {
                    $table->renameColumn( 'price_net', 'price_without_tax' );
                }
                if ( Schema::hasColumn( 'nexopos_orders_products', 'total_price_gross' ) ) {
                    $table->renameColumn( 'total_price_gross', 'total_price_with_tax' );
                }
                if ( Schema::hasColumn( 'nexopos_orders_products', 'total_price_net' ) ) {
                    $table->renameColumn( 'total_price_net', 'total_price_without_tax' );
                }
            } );
        }

        if ( Schema::hasTable( 'nexopos_options' ) ) {
            DB::table( 'nexopos_options' )
                ->where( 'key', 'ns_pos_prefered_price' )
                ->update( [
                    'key' => 'ns_pos_price_with_tax',
                    'value' => DB::raw( "CASE WHEN value = 'gross_prices' THEN 'yes' WHEN value = 'net_prices' THEN 'no' ELSE 'no' END" ),
                ] );
        }

        if ( Schema::hasTable( 'nexopos_orders_settings' ) ) {
            DB::table( 'nexopos_orders_settings' )
                ->where( 'key', 'ns_pos_prefered_price' )
                ->update( [
                    'key' => 'ns_pos_price_with_tax',
                    'value' => DB::raw( "CASE WHEN value = 'gross_prices' THEN 'yes' WHEN value = 'net_prices' THEN 'no' ELSE 'no' END" ),
                ] );
        }
    }
};
