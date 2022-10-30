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
        Schema::table( 'nexopos_customers_coupons', function (Blueprint $table) {
            if ( ! Schema::hasColumn( 'nexopos_customers_coupons', 'active' ) ) {
                $table->boolean( 'active' )->default( true );
            }

            if ( ! Schema::hasColumn( 'nexopos_customers_coupons', 'code' ) ) {
                $table->string( 'code' )->nullable();
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
};
