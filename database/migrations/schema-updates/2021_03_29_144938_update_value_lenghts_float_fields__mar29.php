<?php

use App\Classes\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class UpdateValueLenghtsFloatFieldsMar29 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table( 'nexopos_procurements', function( Blueprint $table ) {
            if ( Schema::hasColumn( 'nexopos_procurements', 'value' ) ) {
                $table->float( 'value', 11, 4 )->change();
            }
        });

        Schema::table( 'nexopos_providers', function( Blueprint $table ) {
            if ( Schema::hasColumn( 'nexopos_providers', 'amount_due' ) ) {
                $table->float( 'amount_due', 11, 4 )->change();
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
