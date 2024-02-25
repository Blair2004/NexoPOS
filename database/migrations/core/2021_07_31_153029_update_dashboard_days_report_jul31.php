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
        Schema::table( 'nexopos_dashboard_days', function ( Blueprint $table ) {
            if ( Schema::hasColumn( 'nexopos_dashboard_days', 'total_cashin' ) ) {
                $table->float( 'total_other_cashin' )->default( 0 );
            }

            if ( Schema::hasColumn( 'nexopos_dashboard_days', 'day_cashin' ) ) {
                $table->float( 'day_other_cashin' )->default( 0 );
            }
        } );
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
};
