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
            if ( ! Schema::hasColumn( 'nexopos_products_unit_quantities', 'net_sale_price' ) ) {
                $table->float( 'net_sale_price' )->default(0)->after( 'sale_price' );
            }

            if ( ! Schema::hasColumn( 'nexopos_products_unit_quantities', 'gross_sale_price' ) ) {
                $table->float( 'gross_sale_price' )->default(0)->after( 'sale_price' );
            }

            if ( ! Schema::hasColumn( 'nexopos_products_unit_quantities', 'net_wholesale_price' ) ) {
                $table->float( 'net_wholesale_price' )->default(0)->after( 'wholesale_price' );
            }

            if ( ! Schema::hasColumn( 'nexopos_products_unit_quantities', 'gross_wholesale_price' ) ) {
                $table->float( 'gross_wholesale_price' )->default(0)->after( 'wholesale_price' );
            }

            if ( ! Schema::hasColumn( 'nexopos_products_unit_quantities', 'net_custom_price' ) ) {
                $table->float( 'net_custom_price' )->default(0)->after( 'custom_price' );
            }

            if ( ! Schema::hasColumn( 'nexopos_products_unit_quantities', 'gross_custom_price' ) ) {
                $table->float( 'gross_custom_price' )->default(0)->after( 'custom_price' );
            }

            /**
             * Dropping...
             */
            if ( Schema::hasColumn( 'nexopos_products_unit_quantities', 'excl_tax_sale_price' ) ) {
                $table->dropColumn( 'excl_tax_sale_price' );
            }

            if ( Schema::hasColumn( 'nexopos_products_unit_quantities', 'incl_tax_sale_price' ) ) {
                $table->dropColumn( 'incl_tax_sale_price' );
            }

            if ( Schema::hasColumn( 'nexopos_products_unit_quantities', 'excl_tax_wholesale_price' ) ) {
                $table->dropColumn( 'excl_tax_wholesale_price' );
            }

            if ( Schema::hasColumn( 'nexopos_products_unit_quantities', 'incl_tax_wholesale_price' ) ) {
                $table->dropColumn( 'incl_tax_wholesale_price' );
            }

            if ( Schema::hasColumn( 'nexopos_products_unit_quantities', 'excl_tax_custom_price' ) ) {
                $table->dropColumn( 'excl_tax_custom_price' );
            }

            if ( Schema::hasColumn( 'nexopos_products_unit_quantities', 'incl_tax_custom_price' ) ) {
                $table->dropColumn( 'incl_tax_custom_price' );
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
