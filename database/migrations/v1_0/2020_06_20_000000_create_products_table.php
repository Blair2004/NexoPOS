<?php
/**
 * Table Migration
 * @package  5.0
**/
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return  void
     */
    public function up()
    {
        if ( ! Schema::hasTable( 'nexopos_products' ) ) {
            Schema::create( 'nexopos_products', function( Blueprint $table ) {
                $table->bigIncrements( 'id' );
                $table->string( 'name' );
                $table->string( 'tax_type' )->nullable(); // inclusive, exclusive
                $table->integer( 'tax_group_id' )->nullable(); 
                $table->float( 'tax_value' )->default(0); // computed automatically
                $table->string( 'product_type' )->default( 'product' ); // product, variation, variable
                $table->string( 'type' )->default( 'tangible' ); // intangible, tangible (or any other extended types)
                $table->float( 'sale_price' )->default(0); // could be 0 if the product support variations
                $table->float( 'excl_tax_sale_price' )->default(0); // must be computed automatically
                $table->float( 'incl_tax_sale_price' )->default(0); // must be computed automatically
                $table->string( 'sale_price_edit' )->default(0); // to let the system consider the price sent by the client
                
                $table->float( 'wholesale_price' )->default(0);
                $table->float( 'incl_tax_wholesale_price' )->default(0); // include tax whole sale price
                $table->float( 'excl_tax_wholesale_price' )->default(0); // exclude tax whole sale price
                $table->float( 'wholesale_price_edit' )->default(0);

                $table->string( 'status' )->default( 'available' ); // available, unavailable
                $table->string( 'stock_management' )->default( 'enabled' ); // enabled, disabled
                $table->string( 'barcode' ); // works if the product type is "product"
                $table->string( 'barcode_type' ); // works if the product type is "product"
                $table->string( 'sku' ); // works if the product type is "product"
                
                $table->text( 'description' )->nullable(); 
                
                $table->integer( 'thumbnail_id' )->nullable(); // link to medias
                $table->integer( 'category_id' )->nullable(); // could be nullable specially if it's a variation
                $table->integer( 'parent_id' )->default(0); // to refer to a parent variable product
                
                $table->integer( 'unit_group' );
                $table->string( 'selling_unit_ids' )->nullable(); // either unit id or set of ids
                $table->string( 'purchase_unit_ids' )->nullable();
                $table->string( 'transfer_unit_ids' )->nullable();

                $table->string( 'on_expiration' )->default( 'prevent_sales' ); // allow_sales, prevent_sales
                $table->boolean( 'expires' )->default(false); // true/false
                $table->datetime( 'expiration' )->nullable();
                $table->integer( 'author' );
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
        if ( Schema::hasTable( 'nexopos_products' ) ) {
            Schema::drop( 'nexopos_products' );
        }
    }
}

