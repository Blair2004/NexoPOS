<?php

use App\Classes\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class UpdateProcurementTableNov2521 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table( 'nexopos_procurements', function( Blueprint $table ) {
            if ( Schema::hasColumn( 'nexopos_procurements', 'value' ) && ! Schema::hasColumn( 'nexopos_procurements', 'cost' ) ) {
                $table->renameColumn( 'value', 'cost' );   
            }
        });

        Schema::table( 'nexopos_procurements', function( Blueprint $table ) {
            if ( ! Schema::hasColumn( 'nexopos_procurements', 'value' ) ) {
                $table->float( 'value', 18, 5 )->default(0);
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
        Schema::table( 'nexopos_procurements', function( Blueprint $table ) {
            if ( ! Schema::hasColumn( 'nexopos_procurements', 'value' ) && Schema::hasColumn( 'nexopos_procurements', 'cost' ) ) {
                $table->renameColumn( 'cost', 'value' );
            }
        });
    }
}
