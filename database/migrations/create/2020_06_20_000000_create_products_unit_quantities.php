<?php
/**
 * Table Migration
**/

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
        if ( ! Schema::hasTable( 'nexopos_products_unit_quantities' ) ) {
            Schema::createIfMissing( 'nexopos_products_unit_quantities', function ( Blueprint $table ) {
                $table->bigIncrements( 'id' );
                $table->integer( 'product_id' );
                $table->string( 'type' )->default( 'product' ); // product | variation
                $table->string( 'preview_url' )->nullable();
                $table->datetime( 'expiration_date' )->nullable();
                $table->integer( 'unit_id' );
                $table->string( 'barcode' )->nullable();
                $table->float( 'quantity', 18, 5 );
                $table->float( 'low_quantity', 18, 5 )->default( 0 );
                $table->boolean( 'stock_alert_enabled' )->default( false );
                $table->float( 'sale_price', 18, 5 )->default( 0 ); // could be 0 if the product support variations
                $table->float( 'sale_price_edit', 18, 5 )->default( 0 ); // to let the system consider the price sent by the client
                $table->float( 'sale_price_without_tax', 18, 5 )->default( 0 ); // must be computed automatically
                $table->float( 'sale_price_with_tax', 18, 5 )->default( 0 ); // must be computed automatically
                $table->float( 'sale_price_tax', 18, 5 )->default( 0 );
                $table->float( 'wholesale_price', 18, 5 )->default( 0 );
                $table->float( 'wholesale_price_edit', 18, 5 )->default( 0 );
                $table->float( 'wholesale_price_with_tax', 18, 5 )->default( 0 ); // include tax whole sale price
                $table->float( 'wholesale_price_without_tax', 18, 5 )->default( 0 ); // exclude tax whole sale price
                $table->float( 'wholesale_price_tax', 18, 5 )->default( 0 );
                $table->float( 'custom_price', 18, 5 )->default( 0 );
                $table->float( 'custom_price_edit', 18, 5 )->default( 0 );
                $table->float( 'custom_price_with_tax', 18, 5 )->default( 0 );
                $table->float( 'custom_price_without_tax', 18, 5 )->default( 0 );
                $table->float( 'custom_price_tax', 18, 5 )->default( 0 );
                $table->boolean( 'visible' )->default( true ); // wether the unit should available for sale.
                $table->integer( 'convert_unit_id' )->nullable();
                $table->float( 'cogs' )->default( 0 );
                $table->string( 'uuid' )->nullable();
                $table->timestamps();
            } );
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if ( Schema::hasTable( 'nexopos_products_unit_quantities' ) ) {
            Schema::dropIfExists( 'nexopos_products_unit_quantities' );
        }
    }
};
