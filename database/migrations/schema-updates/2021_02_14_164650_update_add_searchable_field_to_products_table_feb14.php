<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use App\Classes\Schema;

class UpdateAddSearchableFieldToProductsTableFeb14 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('nexopos_products', function (Blueprint $table) {
            if ( ! Schema::hasColumn( 'nexopos_products', 'searchable' ) ) {
                $table->boolean( 'searchable' )->default(true);
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
        Schema::table('nexopos_products', function (Blueprint $table) {
            if ( Schema::hasColumn( 'nexopos_products', 'searchable' ) ) {
                $table->dropColumn( 'searchable' );
            }
        });
    }
}
