<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateOrdersTableJune15 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table( 'nexopos_orders', function( Blueprint $table ) {
            if ( ! Schema::hasColumn( 'nexopos_orders', 'plate_1' ) ) {
                $table->string( 'plate_1' )->nullable();
            }
            if ( ! Schema::hasColumn( 'nexopos_orders', 'plate_2' ) ) {
                $table->string( 'plate_2' )->nullable();
            }
            if ( ! Schema::hasColumn( 'nexopos_orders', 'plate_3' ) ) {
                $table->string( 'plate_3' )->nullable();
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
}
