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
        if ( ! Schema::hasTable( 'nexopos_products_metas' ) ) {
            Schema::createIfMissing( 'nexopos_products_metas', function ( Blueprint $table ) {
                $table->bigIncrements( 'id' );
                $table->integer( 'product_id' );
                $table->string( 'key' );
                $table->text( 'value' )->nullable();
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
        if ( Schema::hasTable( 'nexopos_products_metas' ) ) {
            Schema::dropIfExists( 'nexopos_products_metas' );
        }
    }
};
