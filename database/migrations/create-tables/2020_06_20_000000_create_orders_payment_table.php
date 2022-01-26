<?php
/**
 * Table Migration
 * @package  5.0
**/

use App\Classes\Hook;
use App\Classes\Schema;;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrdersPaymentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return  void
     */
    public function up()
    {
        if ( ! Schema::hasTable( 'nexopos_orders_payments' ) ) {
            Schema::createIfMissing( 'nexopos_orders_payments', function( Blueprint $table ) {
                $table->bigIncrements( 'id' );
                $table->integer( 'order_id' );
                $table->float( 'value', 18, 5 )->default(0);
                $table->integer( 'author' );
                $table->string( 'identifier' );
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
        if ( Schema::hasTable( 'nexopos_orders_payments' ) ) {
            Schema::dropIfExists( 'nexopos_orders_payments' );
        }
    }
}

