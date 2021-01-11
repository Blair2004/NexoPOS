<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use App\Classes\Schema;

class UpdateNexoposProductsTableOct20 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if ( Schema::hasTable( 'nexopos_products' ) ) {
            if ( ! Schema::hasColumn( 'nexopos_products', 'accurate_stock' ) ) {
                Schema::table( 'nexopos_products', function( Blueprint $table ) {
                    $table->boolean( 'accurate_stock' )->default(0);
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if ( Schema::hasTable( 'nexopos_products' ) ) {
            if ( Schema::hasColumn( 'nexopos_products', 'accurate_stock' ) ) {
                Schema::table( 'nexopos_products', function( Blueprint $table ) {
                    $table->dropColumn( 'accurate_stock' );
                });
            }
        }
    }
}
