<?php

use App\Classes\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table( 'nexopos_products_taxes', function ( Blueprint $table ) {
            if ( ! Schema::hasColumn( 'nexopos_products_taxes', 'unit_quantity_id' ) ) {
                $table->integer( 'unit_quantity_id' );
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
        Schema::table( 'nexopos_products_taxes', function ( Blueprint $table ) {
            if ( Schema::hasColumn( 'nexopos_products_taxes', 'unit_quantity_id' ) ) {
                $table->dropColumn( 'unit_quantity_id' );
            }
        });
    }
};
