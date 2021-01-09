<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use App\Classes\Schema;

class NexoposOrdersProductsTableOct1620 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table( 'nexopos_orders_products', function( Blueprint $table ) {
            if ( Schema::hasColumn( 'nexopos_orders_products', 'tax_id' ) ) {
                $table->renameColumn( 'tax_id', 'tax_group_id' );
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
            if ( Schema::hasColumn( 'nexopos_orders_products', 'tax_group_id' ) ) {
                $table->renameColumn( 'tax_group_id', 'tax_id' );
            }
        });
    }
}
