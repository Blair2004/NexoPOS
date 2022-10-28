<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $object     =   include_once dirname( __FILE__ ) . '/../create-tables/2020_10_10_224639_create_dashboard_table.php';
        $object->up();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $object     =   include_once dirname( __FILE__ ) . '/../create-tables/2020_10_10_224639_create_dashboard_table.php';
        $object->down();
    }
};
