<?php
/**
 * Table Migration
 * @package  5.0
**/

use App\Classes\Hook;
use App\Classes\Schema;;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrdersMetasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return  void
     */
    public function up()
    {
        if ( ! Schema::hasTable( 'nexopos_orders_metas' ) ) {
            Schema::createIfMissing( 'nexopos_orders_metas', function( Blueprint $table ) {
                $table->bigIncrements( 'id' );
                $table->integer( 'order_id' );
                $table->string( 'key' );
                $table->string( 'value' );
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
        if ( Schema::hasTable( 'nexopos_orders_metas' ) ) {
            Schema::dropIfExists( 'nexopos_orders_metas' );
        }
    }
}

