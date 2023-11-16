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
        Schema::table('nexopos_orders', function (Blueprint $table) {
            if ( ! Schema::hasColumn( 'nexopos_orders', 'products_tax_value' ) ) {
                $table->float( 'products_tax_value' )->default(0)->after( 'tax_value' );
            }

            if ( ! Schema::hasColumn( 'nexopos_orders', 'total_tax_value' ) ) {
                $table->float( 'total_tax_value' )->default(0)->after( 'tax_value' );
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
            if ( Schema::hasColumn( 'nexopos_orders', 'products_tax_value' ) ) {
                $table->dropColumn( 'products_tax_value' );
            }
            if ( Schema::hasColumn( 'nexopos_orders', 'total_tax_value' ) ) {
                $table->dropColumn( 'total_tax_value' );
            }
        });
    }
};
