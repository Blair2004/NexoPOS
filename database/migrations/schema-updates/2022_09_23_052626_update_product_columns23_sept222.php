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
        Schema::table( 'nexopos_products_unit_quantities', function( Blueprint $table ) {
            if ( Schema::hasColumn( 'nexopos_products_unit_quantities', 'net_sale_price' ) ) {
                $table->renameColumn( 'net_sale_price', 'sale_price_with_tax' );
            }
            
            if ( Schema::hasColumn( 'nexopos_products_unit_quantities', 'net_wholesale_price' ) ) {
                $table->renameColumn( 'net_wholesale_price', 'wholesale_price_with_tax' );
            }

            if ( Schema::hasColumn( 'nexopos_products_unit_quantities', 'net_custom_price' ) ) {
                $table->renameColumn( 'net_custom_price', 'custom_price_with_tax' );
            }

            if ( Schema::hasColumn( 'nexopos_products_unit_quantities', 'gross_sale_price' ) ) {
                $table->renameColumn( 'gross_sale_price', 'sale_price_without_tax' );
            }

            if ( Schema::hasColumn( 'nexopos_products_unit_quantities', 'gross_wholesale_price' ) ) {
                $table->renameColumn( 'gross_wholesale_price', 'wholesale_price_without_tax' );
            }

            if ( Schema::hasColumn( 'nexopos_products_unit_quantities', 'gross_custom_price' ) ) {
                $table->renameColumn( 'gross_custom_price', 'custom_price_without_tax' );
            }

            if ( ! Schema::hasColumn( 'nexopos_products_unit_quantities', 'gross_price' ) ) {
                $table->float( 'custom_price_tax', 18, 5 )->default(0);
            }
        });

        Schema::table( 'nexopos_orders_products', function( Blueprint $table ) {
            if ( ! Schema::hasTable( 'nexopos_orders_products', 'gross_price' ) ) {
                $table->renameColumn( 'gross_price', 'price_without_tax' );
            }
            
            if ( ! Schema::hasTable( 'nexopos_orders_products', 'net_price' ) ) {
                $table->renameColumn( 'net_price', 'price_with_tax' );
            }

            if ( ! Schema::hasTable( 'nexopos_orders_products', 'total_gross_price' ) ) {
                $table->renameColumn( 'total_gross_price', 'total_price_without_tax' );
            }

            if ( ! Schema::hasTable( 'nexopos_orders_products', 'total_net_price' ) ) {
                $table->renameColumn( 'total_net_price', 'total_price_with_tax' );
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
