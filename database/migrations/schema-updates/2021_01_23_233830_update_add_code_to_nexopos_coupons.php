<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use App\Classes\Schema;

class UpdateAddCodeToNexoposCoupons extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('nexopos_coupons', function (Blueprint $table) {
            if ( ! Schema::hasColumn( 'nexopos_coupons', 'code' ) ) {
                $table->string( 'code' );
            }
        });
        Schema::table('nexopos_customers_coupons', function (Blueprint $table) {
            if ( ! Schema::hasColumn( 'nexopos_customers_coupons', 'code' ) ) {
                $table->string( 'code' );
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
        Schema::table('nexopos_coupons', function (Blueprint $table) {
            if ( Schema::hasColumn( 'nexopos_coupons', 'code' ) ) {
                $table->dropColumn( 'code' );
            }
        });
        Schema::table('nexopos_customers_coupons', function (Blueprint $table) {
            if ( Schema::hasColumn( 'nexopos_customers_coupons', 'code' ) ) {
                $table->dropColumn( 'code' );
            }
        });
    }
}
