<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

include_once( dirname( __FILE__ ) . '/../v1_0/2020_10_10_224639_create_dashboard_table.php' );

class RefreshDashboardTableOct18 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        ( new CreateDashboardTable )->up();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        ( new CreateDashboardTable )->down();
    }
}
