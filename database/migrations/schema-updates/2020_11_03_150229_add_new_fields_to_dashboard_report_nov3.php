<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use App\Classes\Schema;

class AddNewFieldsToDashboardReportNov3 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('nexopos_dashboard_days', function (Blueprint $table) {
            if ( ! Schema::hasColumn( 'nexopos_dashboard_days', 'day_taxes' ) ) {
                $table->float( 'day_taxes' )->default(0);
            }

            if ( ! Schema::hasColumn( 'nexopos_dashboard_days', 'total_taxes' ) ) {
                $table->float( 'total_taxes' )->default(0);
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
        Schema::table('nexopos_dashboard_days', function (Blueprint $table) {
            if ( Schema::hasColumn( 'nexopos_dashboard_days', 'day_taxes' ) ) {
                $table->dropColumn( 'day_taxes' );
            }

            if ( Schema::hasColumn( 'nexopos_dashboard_days', 'total_taxes' ) ) {
                $table->dropColumn( 'total_taxes' );
            }
        });
    }
}
