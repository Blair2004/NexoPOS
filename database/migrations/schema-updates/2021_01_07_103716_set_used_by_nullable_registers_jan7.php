<?php

use App\Classes\Hook;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use App\Classes\Schema;
use Modules\NsMultiStore\Models\Store;

class SetUsedByNullableRegistersJan7 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table( 'nexopos_registers', function( Blueprint $table ) {
            $table->integer( 'used_by' )->nullable()->change();
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
