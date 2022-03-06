<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use App\Classes\Schema;

class UpdateUnitProductQuantitiesTableSept121 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table( 'nexopos_products_unit_quantities', function( Blueprint $table ) {
            if ( ! Schema::hasColumn( 'nexopos_products_unit_quantities', 'custom_price' ) ) {
                $table->float( 'custom_price' )->default(0);
            }
            if ( ! Schema::hasColumn( 'nexopos_products_unit_quantities', 'custom_price_edit' ) ) {
                $table->float( 'custom_price_edit' )->default(0);
            }
            if ( ! Schema::hasColumn( 'nexopos_products_unit_quantities', 'incl_tax_custom_price' ) ) {
                $table->float( 'incl_tax_custom_price' )->default(0);
            }
            if ( ! Schema::hasColumn( 'nexopos_products_unit_quantities', 'excl_tax_custom_price' ) ) {
                $table->float( 'excl_tax_custom_price' )->default(0);
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
}
