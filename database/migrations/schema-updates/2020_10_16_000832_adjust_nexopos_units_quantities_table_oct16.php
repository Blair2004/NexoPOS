<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use App\Classes\Schema;

class AdjustNexoposUnitsQuantitiesTableOct16 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table( 'nexopos_products_unit_quantities', function( Blueprint $table ) {
            foreach([
                'sale_price_tax',
                'sale_price',
                'sale_price_edit',
                'excl_tax_sale_price',
                'incl_tax_sale_price',
                'wholesale_price_tax',
                'wholesale_price',
                'wholesale_price_edit',
                'incl_tax_wholesale_price',
                'excl_tax_wholesale_price',
            ] as $column ) {
                if( ! Schema::hasColumn( 'nexopos_products_unit_quantities', $column ) ) {
                    $table->float( $column )->default(0);
                }
            }

            if( ! Schema::hasColumn( 'nexopos_products_unit_quantities', 'expiration_date' ) ) {
                $table->datetime( 'expiration_date' )->nullable();
            }

            if( ! Schema::hasColumn( 'nexopos_products_unit_quantities', 'preview_url' ) ) {
                $table->string( 'preview_url' )->nullable();
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
        Schema::table( 'nexopos_products_unit_quantities', function( Blueprint $table ) {
            foreach([
                'sale_price_tax',
                'wholesale_price_tax',
                'expiration_date',
                'sale_price',
                'sale_price_edit',
                'excl_tax_sale_price',
                'incl_tax_sale_price',
                'wholesale_price',
                'wholesale_price_edit',
                'incl_tax_wholesale_price',
                'excl_tax_wholesale_price',
                'preview_url'
            ] as $column ) {
                if( Schema::hasColumn( 'nexopos_products_unit_quantities', $column ) ) {
                    $table->dropColumn( $column );
                }
            }
        });
    }
}
