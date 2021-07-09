<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use App\Classes\Schema;

class UpdateNexoposOrdersCouponsTableJanv26 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table( 'nexopos_orders_coupons', function( Blueprint $table ) {
            if ( ! Schema::hasColumn( 'nexopos_orders_coupons', 'code' ) ) {
                $table->string( 'code' );
            }
            if ( ! Schema::hasColumn( 'nexopos_orders_coupons', 'name' ) ) {
                $table->string( 'name' );
            }
            if ( ! Schema::hasColumn( 'nexopos_orders_coupons', 'author' ) ) {
                $table->string( 'author' );
            }
            if ( Schema::hasColumn( 'nexopos_orders_coupons', 'coupon_id' ) ) {
                $table->renameColumn( 'coupon_id', 'customer_coupon_id' );
            }
            if ( ! Schema::hasColumn( 'nexopos_orders_coupons', 'type' ) ) {
                $table->string( 'type' );
            }
            if ( ! Schema::hasColumn( 'nexopos_orders_coupons', 'discount_value' ) ) {
                $table->float( 'discount_value' )->default(0);
            }
            if ( ! Schema::hasColumn( 'nexopos_orders_coupons', 'minimum_cart_value' ) ) {
                $table->float( 'minimum_cart_value' )->default(0);
            }
            if ( ! Schema::hasColumn( 'nexopos_orders_coupons', 'maximum_cart_value' ) ) {
                $table->float( 'maximum_cart_value' )->default(0);
            }
            if ( ! Schema::hasColumn( 'nexopos_orders_coupons', 'limit_usage' ) ) {
                $table->integer( 'limit_usage' )->default(0);
            }
            if ( ! Schema::hasColumn( 'nexopos_orders_coupons', 'value' ) ) {
                $table->float( 'value' )->default(0);
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
