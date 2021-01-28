<?php

use App\Classes\Hook;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use App\Classes\Schema;;

class CreateCustomersCouponsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::createIfMissing( 'nexopos_customers_coupons', function (Blueprint $table) {
            $table->id();
            $table->string( 'name' );
            $table->integer( 'usage' )->default(0);
            $table->integer( 'limit_usage' );
            $table->boolean( 'active' )->default( true );
            $table->string( 'code' );
            $table->integer( 'coupon_id' );
            $table->integer( 'customer_id' );
            $table->integer( 'author' );
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists( 'nexopos_customers_coupons' );
        Schema::dropIfExists( 'nexopos_customers_coupons_products' );
        Schema::dropIfExists( 'nexopos_customers_coupons_categories' );
    }
}
