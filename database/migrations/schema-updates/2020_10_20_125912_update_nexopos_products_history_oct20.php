<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use App\Classes\Schema;

class UpdateNexoposProductsHistoryOct20 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if ( Schema::hasTable( 'nexopos_products_histories' ) ) {
            if ( ! Schema::hasColumn( 'nexopos_products_histories', 'description' ) ) {
                Schema::table( 'nexopos_products_histories', function( Blueprint $table ) {
                    $table->text( 'description' )->nullable();
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
        if ( Schema::hasTable( 'nexopos_products_histories' ) ) {
            if ( Schema::hasColumn( 'nexopos_products_histories', 'description' ) ) {
                Schema::table( 'nexopos_products_histories', function( Blueprint $table ) {
                    $table->dropColumn( 'description' );
                });
            }
        }
    }
}
