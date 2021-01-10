<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use App\Classes\Schema;

class AddDescriptionToRegisterHistoryJanv8 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('nexopos_registers_history', function (Blueprint $table) {
            if ( ! Schema::hasColumn( 'nexopos_registers_history', 'description' ) ) {
                $table->text( 'description' )->nullable();
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
        Schema::table('nexopos_registers_history', function (Blueprint $table) {
            if ( Schema::hasColumn( 'nexopos_registers_history', 'description' ) ) {
                $table->dropColumn( 'description' );
            }
        });
    }
}
