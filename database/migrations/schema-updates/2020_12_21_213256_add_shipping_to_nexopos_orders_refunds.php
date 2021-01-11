<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use App\Classes\Schema;

class AddShippingToNexoposOrdersRefunds extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('nexopos_orders_refunds', function (Blueprint $table) {
            if ( ! Schema::hasColumn( 'nexopos_orders_refunds', 'shipping' ) ) {
                $table->float( 'shipping' )->default(0);
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
        Schema::table('nexopos_orders_refunds', function (Blueprint $table) {
            if ( Schema::hasColumn( 'nexopos_orders_refunds', 'shipping' ) ) {
                $table->dropColumn( 'shipping' );
            }
        });
    }
}
