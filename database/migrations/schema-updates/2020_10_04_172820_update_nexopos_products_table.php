<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use App\Classes\Schema;

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
            if ( ! Schema::hasColumn( 'nexopos_products', 'wholesale_tax_value' ) ) {
                $table->float( 'wholesale_tax_value' )->default(0);
            }

            if ( ! Schema::hasColumn( 'nexopos_products', 'sale_tax_value' ) ) {
                $table->float( 'sale_tax_value' )->default(0);
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
        Schema::table( 'nexopos_products', function( Blueprint $table ) {
            if ( Schema::hasColumn( 'nexopos_products', 'wholesale_tax_value' ) ) {
                $table->dropColumn( 'wholesale_tax_value' );
            }

            if ( Schema::hasColumn( 'nexopos_products', 'sale_tax_value' ) ) {
                $table->dropColumn( 'sale_tax_value' );
            }            
        });
    }
}
