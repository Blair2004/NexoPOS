<?php

use Illuminate\Database\Migrations\Migration;

class UpdateCreateOptionsForOrderType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        ns()->option->set( 'ns_pos_order_types', [ 'takeaway', 'delivery' ]);
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
