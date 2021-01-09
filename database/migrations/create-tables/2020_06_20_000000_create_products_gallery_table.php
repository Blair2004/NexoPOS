<?php
/**
 * Table Migration
 * @package  5.0
**/

use App\Classes\Hook;
use App\Classes\Schema;;
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
            Schema::createIfMissing( 'nexopos_products_galleries', function( Blueprint $table ) {
                $table->bigIncrements( 'id' );
                $table->string( 'name' )->nullable();
                $table->integer( 'product_id' );
                $table->integer( 'media_id' )->nullable();
                $table->string( 'url' )->nullable();
                $table->integer( 'order' )->default(0);
                $table->boolean( 'featured' )->default(0);
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
        if ( Schema::hasTable( 'nexopos_products_galleries' ) ) {
            Schema::dropIfExists( 'nexopos_products_galleries' );
        }
    }
}

