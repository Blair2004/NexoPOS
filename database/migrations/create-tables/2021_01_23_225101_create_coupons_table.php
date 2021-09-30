<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use App\Classes\Schema;

class CreateCouponsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::createIfMissing( 'nexopos_coupons', function (Blueprint $table) {
            $table->id();
            $table->string( 'name' );
            $table->string( 'code' );
            $table->string( 'type' )->default( 'discount' ); // percentage_discount, flat_discount, giveaway
            $table->float( 'discount_value', 18, 5 )->default(0); // flat value or percentage
            $table->datetime( 'valid_until' )->nullable(); // unlimited
            $table->float( 'minimum_cart_value', 18, 5 )->default(0)->nullable();
            $table->float( 'maximum_cart_value', 18, 5 )->default(0)->nullable();
            $table->datetime( 'valid_hours_start' )->nullable();
            $table->datetime( 'valid_hours_end' )->nullable();
            $table->float( 'limit_usage', 18, 5 )->default(0); // unlimited
            $table->integer( 'author' );
            $table->timestamps();
        });

        Schema::createIfMissing( 'nexopos_coupons_products', function (Blueprint $table) {
            $table->id();
            $table->integer( 'coupon_id' );
            $table->integer( 'product_id' );
        });

        Schema::createIfMissing( 'nexopos_coupons_categories', function (Blueprint $table) {
            $table->id();
            $table->integer( 'coupon_id' );
            $table->integer( 'category_id' );
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('nexopos_coupons');
        Schema::dropIfExists('nexopos_coupons_products');
        Schema::dropIfExists('nexopos_coupons_categories');
    }
}
