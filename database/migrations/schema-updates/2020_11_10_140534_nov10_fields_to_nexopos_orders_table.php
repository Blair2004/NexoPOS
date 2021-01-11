<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use App\Classes\Schema;

class Nov10FieldsToNexoposOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('nexopos_orders', function (Blueprint $table) {
            if ( ! Schema::hasColumn( 'nexopos_orders', 'voidance_reason' ) ) {
                $table->text( 'voidance_reason' )->nullable();
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
        Schema::table('nexopos_orders', function (Blueprint $table) {
            if ( Schema::hasColumn( 'nexopos_orders', 'voidance_reason' ) ) {
                $table->dropColumn( 'voidance_reason' );
            }
        });
    }
}
