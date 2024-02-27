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
        if ( ! Schema::hasTable( 'nexopos_products_histories' ) ) {
            Schema::createIfMissing( 'nexopos_products_histories', function ( Blueprint $table ) {
                $table->bigIncrements( 'id' );
                $table->integer( 'product_id' );
                $table->integer( 'procurement_id' )->nullable();
                $table->integer( 'procurement_product_id' )->nullable();
                $table->integer( 'order_id' )->nullable();
                $table->integer( 'order_product_id' )->nullable();
                $table->string( 'operation_type' ); // sale, procurement, adjustment, return, defective
                $table->integer( 'unit_id' );
                $table->float( 'before_quantity', 18, 5 )->nullable();
                $table->float( 'quantity', 18, 5 ); // current unit quantity
                $table->float( 'after_quantity', 18, 5 )->nullable();
                $table->float( 'unit_price', 18, 5 ); // could be the cost of the procurement, the lost (defective)
                $table->float( 'total_price', 18, 5 ); // could be the cost of the procurement, the lost (defective)
                $table->text( 'description' )->nullable();
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
        if ( Schema::hasTable( 'nexopos_products_histories' ) ) {
            Schema::dropIfExists( 'nexopos_products_histories' );
        }
    }
};
