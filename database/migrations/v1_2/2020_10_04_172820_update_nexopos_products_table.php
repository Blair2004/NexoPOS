<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateNexoposProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table( 'nexopos_products', function( Blueprint $table ) {
            $table->float( 'wholesale_tax_value' )->default(0);
            $table->float( 'sale_tax_value' )->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table( 'nexopos_products', function( Blueprint $table ) {
            $table->dropColumn( 'wholesale_tax_value' );
            $table->dropColumn( 'sale_tax_value' );
        });
    }
}
