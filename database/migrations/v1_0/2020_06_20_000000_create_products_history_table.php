<?php
/**
 * Table Migration
 * @package  5.0
**/

use App\Classes\Hook;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductsHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return  void
     */
    public function up()
    {
        if ( ! Schema::hasTable( Hook::filter( 'ns-table-prefix', 'nexopos_products_histories' ) ) ) {
            Schema::create( Hook::filter( 'ns-table-prefix', 'nexopos_products_histories' ), function( Blueprint $table ) {
                $table->bigIncrements( 'id' );
                $table->integer( 'product_id' );
                $table->integer( 'procurement_id' )->nullable();
                $table->integer( 'procurement_product_id' )->nullable();
                $table->integer( 'order_id' )->nullable();
                $table->string( 'operation_type' ); // sale, procurement, adjustment, return, defective
                $table->integer( 'unit_id' );
                $table->float( 'before_quantity' )->nullable();
                $table->float( 'quantity' ); // current unit quantity
                $table->float( 'after_quantity' )->nullable();
                $table->float( 'unit_price' ); // could be the cost of the procurement, the lost (defective)
                $table->float( 'total_price' ); // could be the cost of the procurement, the lost (defective)
                $table->text( 'description' )->nullable();
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
        if ( Schema::hasTable( Hook::filter( 'ns-table-prefix', 'nexopos_products_histories' ) ) ) {
            Schema::dropIfExists( Hook::filter( 'ns-table-prefix', 'nexopos_products_histories' ) );
        }
    }
}

