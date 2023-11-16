<?php

use App\Classes\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table( 'nexopos_products_unit_quantities', function ( Blueprint $table ) {
            if ( Schema::hasColumn( 'nexopos_products_unit_quantities', 'net_sale_price' ) && ! Schema::hasColumn( 'nexopos_products_unit_quantities', 'sale_price_with_tax' ) ) {
                $table->renameColumn( 'net_sale_price', 'sale_price_with_tax' );
            }

            if ( Schema::hasColumn( 'nexopos_products_unit_quantities', 'net_wholesale_price' ) && ! Schema::hasColumn( 'nexopos_products_unit_quantities', 'wholesale_price_with_tax' ) ) {
                $table->renameColumn( 'net_wholesale_price', 'wholesale_price_with_tax' );
            }

            if ( Schema::hasColumn( 'nexopos_products_unit_quantities', 'net_custom_price' ) && ! Schema::hasColumn( 'nexopos_products_unit_quantities', 'custom_price_with_tax' ) ) {
                $table->renameColumn( 'net_custom_price', 'custom_price_with_tax' );
            }

            if ( Schema::hasColumn( 'nexopos_products_unit_quantities', 'gross_sale_price' ) && ! Schema::hasColumn( 'nexopos_products_unit_quantities', 'sale_price_without_tax' ) ) {
                $table->renameColumn( 'gross_sale_price', 'sale_price_without_tax' );
            }

            if ( Schema::hasColumn( 'nexopos_products_unit_quantities', 'gross_wholesale_price' ) && ! Schema::hasColumn( 'nexopos_products_unit_quantities', 'wholesale_price_without_tax' ) ) {
                $table->renameColumn( 'gross_wholesale_price', 'wholesale_price_without_tax' );
            }

            if ( Schema::hasColumn( 'nexopos_products_unit_quantities', 'gross_custom_price' ) && ! Schema::hasColumn( 'nexopos_products_unit_quantities', 'custom_price_without_tax' ) ) {
                $table->renameColumn( 'gross_custom_price', 'custom_price_without_tax' );
            }

            if ( ! Schema::hasColumn( 'nexopos_products_unit_quantities', 'custom_price_tax' ) ) {
                $table->float( 'custom_price_tax', 18, 5 )->default(0);
            }
        });

        Schema::table( 'nexopos_orders_products', function ( Blueprint $table ) {
            if ( Schema::hasColumn( 'nexopos_orders_products', 'gross_price' ) && ! Schema::hasColumn( 'nexopos_orders_products', 'price_without_tax' ) ) {
                $table->renameColumn( 'gross_price', 'price_without_tax' );
            }

            if ( Schema::hasColumn( 'nexopos_orders_products', 'net_price' ) && ! Schema::hasColumn( 'nexopos_orders_products', 'price_with_tax' ) ) {
                $table->renameColumn( 'net_price', 'price_with_tax' );
            }

            if ( Schema::hasColumn( 'nexopos_orders_products', 'total_gross_price' ) && ! Schema::hasColumn( 'nexopos_orders_products', 'total_price_without_tax' ) ) {
                $table->renameColumn( 'total_gross_price', 'total_price_without_tax' );
            }

            if ( Schema::hasColumn( 'nexopos_orders_products', 'total_net_price' ) && ! Schema::hasColumn( 'nexopos_orders_products', 'total_price_with_tax' ) ) {
                $table->renameColumn( 'total_net_price', 'total_price_with_tax' );
            }
        });

        Schema::table( 'nexopos_orders', function ( Blueprint $table ) {
            if ( Schema::hasColumn( 'nexopos_orders', 'net_total' ) ) {
                $table->renameColumn( 'net_total', 'total_with_tax' );
            }

            if ( Schema::hasColumn( 'nexopos_orders', 'gross_total' ) ) {
                $table->renameColumn( 'gross_total', 'total_without_tax' );
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
