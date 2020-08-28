<?php
/**
 * Table Migration
 * @package  5.0
**/
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductsGalleryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return  void
     */
    public function up()
    {
        if ( ! Schema::hasTable( 'nexopos_products_galleries' ) ) {
            Schema::create( 'nexopos_products_galleries', function( Blueprint $table ) {
                $table->bigIncrements( 'id' );
                $table->string( 'name' );
                $table->integer( 'product_id' );
                $table->integer( 'media_id' );
                $table->integer( 'order' )->default(0);
                $table->boolean( 'featured' )->default(0);
                $table->integer( 'author' );
                $table->string( 'uuid' );
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
        if ( Schema::hasTable( 'nexopos_products_galleries' ) ) {
            Schema::drop( 'nexopos_products_galleries' );
        }
    }
}

