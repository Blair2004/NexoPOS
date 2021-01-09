<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use App\Classes\Schema;

class Nov14FixNexoposOrdersProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table( 'nexopos_orders_products', function( Blueprint $table ) {
            if ( Schema::hasColumn( 'nexopos_orders_products', 'unit_id' ) ) {
                $table->integer( 'unit_id' )->change();
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
        Schema::table( 'nexopos_orders_products', function( Blueprint $table ) {
            if ( Schema::hasColumn( 'nexopos_orders_products', 'unit_id' ) ) {
                $table->float( 'unit_id' )->change();
            }
        });
    }
}
