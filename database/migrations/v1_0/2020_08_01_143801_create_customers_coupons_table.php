<?php

use App\Classes\Hook;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomersCouponsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create( Hook::filter( 'ns-table-prefix', 'nexopos_customers_coupons' ), function (Blueprint $table) {
            $table->id();
            $table->string( 'name' );
            $table->string( 'type' )->default( 'discount' ); // percentage_discount, flat_discount, giveaway
            $table->float( 'discount_value' )->default(0); // flat value or percentage
            $table->datetime( 'valid_until' )->nullable(); // unlimited
            $table->float( 'minimum_cart_value' )->default(0)->nullable();
            $table->float( 'maximum_cart_value' )->default(0)->nullable();
            $table->datetime( 'valid_hours_start' )->nullable();
            $table->datetime( 'valid_hours_end' )->nullable();
            $table->integer( 'customer_id' )->nullable();
            $table->boolean( 'assigned' )->default(false);
            $table->float( 'limit_usage' )->default(0); // unlimited
            $table->integer( 'author' );
            $table->timestamps();
        });

        Schema::create( Hook::filter( 'ns-table-prefix', 'nexopos_customers_coupons_products' ), function (Blueprint $table) {
            $table->id();
            $table->integer( 'coupon_id' );
            $table->integer( 'product_id' );
        });

        Schema::create( Hook::filter( 'ns-table-prefix', 'nexopos_customers_coupons_categories' ), function (Blueprint $table) {
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
        Schema::dropIfExists( Hook::filter( 'ns-table-prefix', 'nexopos_customers_coupons') );
        Schema::dropIfExists( Hook::filter( 'ns-table-prefix', 'nexopos_customers_coupons_products') );
        Schema::dropIfExists( Hook::filter( 'ns-table-prefix', 'nexopos_customers_coupons_categories') );
    }
}
