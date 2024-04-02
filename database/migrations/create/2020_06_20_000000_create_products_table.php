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
        if ( ! Schema::hasTable( 'nexopos_products' ) ) {
            Schema::createIfMissing( 'nexopos_products', function ( Blueprint $table ) {
                $table->bigIncrements( 'id' );
                $table->string( 'name' );
                $table->string( 'tax_type' )->nullable(); // inclusive, exclusive
                $table->integer( 'tax_group_id' )->nullable();
                $table->float( 'tax_value', 18, 5 )->default( 0 ); // computed automatically
                $table->string( 'product_type' )->default( 'product' ); // product, variation, variable
                $table->string( 'type' )->default( 'tangible' ); // intangible, tangible (or any other extended types)
                $table->boolean( 'accurate_tracking' )->default( 0 ); // @since db 1.3
                $table->boolean( 'auto_cogs' )->default( true ); // @since v5.0.x
                $table->string( 'status' )->default( 'available' ); // available, unavailable
                $table->string( 'stock_management' )->default( 'enabled' ); // enabled, disabled
                $table->string( 'barcode' ); // works if the product type is "product"
                $table->string( 'barcode_type' ); // works if the product type is "product"
                $table->string( 'sku' ); // works if the product type is "product"

                $table->text( 'description' )->nullable();

                $table->integer( 'thumbnail_id' )->nullable(); // link to medias
                $table->integer( 'category_id' )->nullable(); // could be nullable specially if it's a variation
                $table->integer( 'parent_id' )->default( 0 ); // to refer to a parent variable product

                $table->integer( 'unit_group' );
                $table->string( 'on_expiration' )->default( 'prevent_sales' ); // allow_sales, prevent_sales
                $table->boolean( 'expires' )->default( false ); // true/false
                $table->boolean( 'searchable' )->default( true ); // whether the product can be searched on the POS.
                $table->integer( 'author' );
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
        if ( Schema::hasTable( 'nexopos_products' ) ) {
            Schema::dropIfExists( 'nexopos_products' );
        }
    }
};
