<?php

use App\Classes\Hook;
use App\Classes\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class UpdateNexoOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table( 'nexopos_orders', function( Blueprint $table ) {
            if ( ! Schema::hasColumn( 'nexopos_orders', 'tax_value' ) ) {
                $table->float( 'tax_value' )->default(0);
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
        Schema::table( 'nexopos_orders', function( Blueprint $table ) {
            if ( Schema::hasColumn( 'nexopos_orders', 'tax_value' ) ) {
                $table->dropColumn( 'tax_value' );
            }
        });
    }
}
