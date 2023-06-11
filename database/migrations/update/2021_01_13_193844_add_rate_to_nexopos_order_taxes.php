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
        Schema::table('nexopos_orders_taxes', function (Blueprint $table) {
            if ( ! Schema::hasColumn( 'nexopos_orders_taxes', 'rate' ) ) {
                $table->float( 'rate' )->nullable();
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
        Schema::table('nexopos_orders_taxes', function (Blueprint $table) {
            if ( Schema::hasColumn( 'nexopos_orders_taxes', 'rate' ) ) {
                $table->dropColumn( 'rate' );
            }
        });
    }
};
