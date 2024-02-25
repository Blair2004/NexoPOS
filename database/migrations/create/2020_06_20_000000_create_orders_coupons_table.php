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
        if ( ! Schema::hasTable( 'nexopos_orders_coupons' ) ) {
            Schema::createIfMissing( 'nexopos_orders_coupons', function ( Blueprint $table ) {
                $table->bigIncrements( 'id' );
                $table->string( 'code' );
                $table->string( 'name' );
                $table->integer( 'customer_coupon_id' );
                $table->integer( 'coupon_id' );
                $table->integer( 'order_id' );
                $table->string( 'type' ); // discount_percentage, flat_percentage
                $table->float( 'discount_value', 18, 5 );
                $table->float( 'minimum_cart_value', 18, 5 )->default( 0 );
                $table->float( 'maximum_cart_value', 18, 5 )->default( 0 );
                $table->integer( 'limit_usage' )->default( 0 );
                $table->float( 'value', 18, 5 )->default( 0 );
                $table->integer( 'author' );
                $table->boolean( 'counted' )->default( false );
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
        if ( Schema::hasTable( 'nexopos_orders_coupons' ) ) {
            Schema::dropIfExists( 'nexopos_orders_coupons' );
        }
    }
};
