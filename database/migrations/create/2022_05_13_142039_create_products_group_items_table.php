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
        if ( ! Schema::hasTable( 'nexopos_products_subitems' ) ) {
            Schema::create( 'nexopos_products_subitems', function ( Blueprint $table ) {
                $table->id();
                $table->integer( 'parent_id' );
                $table->integer( 'product_id' );
                $table->integer( 'unit_id' );
                $table->integer( 'unit_quantity_id' );
                $table->float( 'sale_price' )->default( 0 );
                $table->float( 'quantity' )->default( 0 );
                $table->float( 'total_price' )->default( 0 );
                $table->integer( 'author' );
                $table->timestamps();
            } );
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists( 'nexopos_products_subitems' );
    }
};
