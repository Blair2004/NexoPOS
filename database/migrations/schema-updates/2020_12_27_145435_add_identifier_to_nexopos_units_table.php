<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIdentifierToNexoposUnitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('nexopos_units', function (Blueprint $table) {
            if ( ! Schema::hasColumn( 'nexopos_units', 'identifier' ) ) {
                $table->string( 'identifier' )->unique();
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
        Schema::table('nexopos_units', function (Blueprint $table) {
            if ( Schema::hasColumn( 'nexopos_units', 'identifier' ) ) {
                $table->dropColumn( 'identifier' );
            }
        });
    }
}
