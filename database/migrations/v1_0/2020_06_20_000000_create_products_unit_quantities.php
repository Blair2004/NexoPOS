<?php
/**
 * Table Migration
 * @package  5.0
**/
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductsUnitQuantities extends Migration
{
    /**
     * Run the migrations.
     *
     * @return  void
     */
    public function up()
    {
        if ( ! Schema::hasTable( 'nexopos_products_unit_quantities' ) ) {
            Schema::create( 'nexopos_products_unit_quantities', function( Blueprint $table ) {
                $table->bigIncrements( 'id' );
                $table->integer( 'product_id' );
                $table->string( 'type' )->default( 'product' ); // product | variation
                $table->integer( 'unit_id' );
                $table->float( 'quantity' );
                $table->float( 'sale_price' )->default(0); // could be 0 if the product support variations
                $table->float( 'sale_price_edit' )->default(0); // to let the system consider the price sent by the client
                $table->float( 'excl_tax_sale_price' )->default(0); // must be computed automatically
                $table->float( 'incl_tax_sale_price' )->default(0); // must be computed automatically
                $table->float( 'sale_price_tax' )->default(0);
                $table->float( 'wholesale_price' )->default(0);
                $table->float( 'wholesale_price_edit' )->default(0);
                $table->float( 'incl_tax_wholesale_price' )->default(0); // include tax whole sale price
                $table->float( 'excl_tax_wholesale_price' )->default(0); // exclude tax whole sale price
                $table->float( 'wholesale_price_tax' )->default(0);
                $table->string( 'uuid' )->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return  void
     */
    public function down()
    {
        if ( Schema::hasTable( 'nexopos_products_unit_quantities' ) ) {
            Schema::drop( 'nexopos_products_unit_quantities' );
        }
    }
}

