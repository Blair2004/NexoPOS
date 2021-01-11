<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use App\Classes\Schema;

class UpdateDashboardDaysTableOct15 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table( 'nexopos_dashboard_days', function( Blueprint $table ) {
            if ( ! Schema::hasColumn( 'nexopos_dashboard_days', 'day_of_year' ) ) {
                $table->integer( 'day_of_year' )->nullable();
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
        Schema::table( 'nexopos_dashboard_days', function( Blueprint $table ) {
            if ( Schema::hasColumn( 'nexopos_dashbaord_days', 'day_of_year' ) ) {
                $table->dropColumn( 'day_of_year' );
            }
        });
    }
}
