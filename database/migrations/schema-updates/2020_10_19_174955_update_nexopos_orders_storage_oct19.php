<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use App\Classes\Schema;

class UpdateNexoposOrdersStorageOct19 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if ( Schema::hasTable( 'nexopos_orders_storage' ) ) {
            if ( Schema::hasColumn( 'nexopos_orders_storage', 'product_unit_quantity_id' ) ) {
                Schema::table( 'nexopos_orders_storage', function( Blueprint $table ) {
                    $table->renameColumn( 'product_unit_quantity_id', 'unit_quantity_id' );
                });
            }

            if ( Schema::hasColumn( 'nexopos_orders_storage', 'product_unit_id' ) ) {
                Schema::table( 'nexopos_orders_storage', function( Blueprint $table ) {
                    $table->renameColumn( 'product_unit_id', 'unit_id' );
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if ( Schema::hasTable( 'nexopos_orders_storage' ) ) {
            if ( Schema::hasColumn( 'nexopos_orders_storage', 'unit_quantity_id' ) ) {
                Schema::table( 'nexopos_orders_storage', function( Blueprint $table ) {
                    $table->renameColumn( 'unit_quantity_id', 'product_unit_quantity_id' );
                });
            }

            if ( Schema::hasColumn( 'nexopos_orders_storage', 'unit_id' ) ) {
                Schema::table( 'nexopos_orders_storage', function( Blueprint $table ) {
                    $table->renameColumn( 'unit_id', 'product_unit_id' );
                });
            }
        }
    }
}
