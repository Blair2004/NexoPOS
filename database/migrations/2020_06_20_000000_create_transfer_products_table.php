<?php
/**
 * Table Migration
 * @package  5.0
**/
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransferProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return  void
     */
    public function up()
    {
        /**
         * i believe it should support the unit of measure.
         */
        if ( ! Schema::hasTable( 'nexopos_transfers_products' ) ) {
            Schema::create( 'nexopos_transfers_products', function( Blueprint $table ) {
                $table->bigIncrements( 'id' );
                $table->string( 'product_id' );
                $table->integer( 'transfer_id' );
                $table->float( 'quantity' );
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
        if ( Schema::hasTable( 'nexopos_transfers_products' ) ) {
            Schema::drop( 'nexopos_transfers_products' );
        }
    }
}

