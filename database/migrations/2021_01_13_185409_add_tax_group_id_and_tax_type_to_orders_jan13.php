<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use App\Classes\Schema;

class AddTaxGroupIdAndTaxTypeToOrdersJan13 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('nexopos_orders', function (Blueprint $table) {
            if ( ! Schema::hasColumn( 'nexopos_orders', 'tax_group_id' ) ) {
                $table->integer( 'tax_group_id' )->nullable();
            }
            if ( ! Schema::hasColumn( 'nexopos_orders', 'tax_type' ) ) {
                $table->string( 'tax_type' )->nullable();
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
            if ( Schema::hasColumn( 'nexopos_orders', 'tax_group_id' ) ) {
                $table->dropColumn( 'tax_group_id' );
            }
            if ( Schema::hasColumn( 'nexopos_orders', 'tax_type' ) ) {
                $table->dropColumn( 'tax_type' );
            }
        });
    }
}
