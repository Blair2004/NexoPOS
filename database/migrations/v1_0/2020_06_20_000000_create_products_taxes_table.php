<?php
/**
 * Table Migration
 * @package  5.0
**/
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductsTaxesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return  void
     */
    public function up()
    {
        if ( ! Schema::hasTable( 'nexopos_products_taxes' ) ) {
            Schema::create( 'nexopos_products_taxes', function( Blueprint $table ) {
                $table->bigIncrements( 'id' );
                $table->integer( 'product_id' );
                $table->string( 'tax_id' ); // grouped, simple
                $table->string( 'name' );
                $table->float( 'rate' );
                $table->float( 'value' ); // actual computed tax value
                $table->integer( 'author' );
                $table->string( 'uuid' )->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return  void
     */
    public function down()
    {
        if ( Schema::hasTable( 'nexopos_products_taxes' ) ) {
            Schema::drop( 'nexopos_products_taxes' );
        }
    }
}

