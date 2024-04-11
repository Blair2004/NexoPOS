<?php

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
        Schema::createIfMissing( 'nexopos_coupons', function ( Blueprint $table ) {
            $table->id();
            $table->string( 'name' );
            $table->string( 'code' );
            $table->string( 'type' )->default( 'discount' ); // percentage_discount, flat_discount, giveaway
            $table->float( 'discount_value', 18, 5 )->default( 0 ); // flat value or percentage
            $table->datetime( 'valid_until' )->nullable(); // unlimited
            $table->float( 'minimum_cart_value', 18, 5 )->default( 0 )->nullable();
            $table->float( 'maximum_cart_value', 18, 5 )->default( 0 )->nullable();
            $table->datetime( 'valid_hours_start' )->nullable();
            $table->datetime( 'valid_hours_end' )->nullable();
            $table->float( 'limit_usage', 18, 5 )->default( 0 ); // unlimited
            $table->string( 'groups_id' )->nullable();
            $table->string( 'customers_id' )->nullable();
            $table->integer( 'author' );
            $table->timestamps();
        } );

        Schema::createIfMissing( 'nexopos_coupons_products', function ( Blueprint $table ) {
            $table->id();
            $table->integer( 'coupon_id' );
            $table->integer( 'product_id' );
        } );

        Schema::createIfMissing( 'nexopos_coupons_categories', function ( Blueprint $table ) {
            $table->id();
            $table->integer( 'coupon_id' );
            $table->integer( 'category_id' );
        } );

        Schema::createIfMissing( 'nexopos_coupons_customers', function ( Blueprint $table ) {
            $table->id();
            $table->integer( 'coupon_id' );
            $table->integer( 'customer_id' );
        } );

        Schema::createIfMissing( 'nexopos_coupons_customers_groups', function ( Blueprint $table ) {
            $table->id();
            $table->integer( 'coupon_id' );
            $table->integer( 'group_id' );
        } );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists( 'nexopos_coupons' );
        Schema::dropIfExists( 'nexopos_coupons_products' );
        Schema::dropIfExists( 'nexopos_coupons_categories' );
        Schema::dropIfExists( 'nexopos_coupons_customers' );
        Schema::dropIfExists( 'nexopos_coupons_customers_groups' );
    }
};
