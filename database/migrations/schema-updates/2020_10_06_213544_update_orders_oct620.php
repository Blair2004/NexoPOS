<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use App\Classes\Schema;

class UpdateOrdersOct620 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table( 'nexopos_orders', function( Blueprint $table ) {
            if ( Schema::hasColumn( 'nexopos_orders', 'discount_rate' ) ) {
                $table->renameColumn( 'discount_rate', 'discount_percentage' );
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
        Schema::table( 'nexopos_orders', function( Blueprint $table ) {
            if ( Schema::hasColumn( 'nexopos_orders', 'discount_percentage' ) ) {
                $table->renameColumn( 'discount_percentage', 'discount_rate' );
            }
        });
    }
}
