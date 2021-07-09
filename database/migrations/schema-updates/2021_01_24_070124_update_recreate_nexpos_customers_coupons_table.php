<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use App\Classes\Schema;

class UpdateRecreateNexposCustomersCouponsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists( 'nexopos_customers_coupons' );
        Schema::createIfMissing( 'nexopos_customers_coupons', function (Blueprint $table) {
            $table->id();
            $table->string( 'name' );
            $table->integer( 'usage' )->default(0);
            $table->integer( 'limit' );
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
    }
}
