<?php

use App\Classes\Hook;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use App\Classes\Schema;;

class Nov17AddFieldsToNexoposOrdersProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::createIfMissing( 'nexopos_orders_refunds', function( Blueprint $table ) {
            $table->bigIncrements( 'id' );
            $table->integer( 'order_id' );
            $table->integer( 'author' );
            $table->float( 'total', 11, 5 );
            $table->float( 'tax_value' )->default(0);
            $table->float( 'shipping', 11, 5 );
            $table->string( 'payment_method' );
            $table->timestamps();
        });

        Schema::createIfMissing( 'nexopos_orders_products_refunds', function (Blueprint $table) {
            $table->bigIncrements( 'id' );
            $table->integer( 'order_id' );
            $table->integer( 'order_refund_id' );
            $table->integer( 'order_product_id' );
            $table->integer( 'unit_id' );
            $table->integer( 'product_id' );
            $table->float( 'unit_price', 11, 5 );
            $table->float( 'tax_value' )->default(0);
            $table->float( 'quantity', 11, 5 );
            $table->float( 'total_price', 11, 5 );
            $table->string( 'condition' ); // either unspoiled, damaged
            $table->text( 'description' )->nullable();
            $table->integer( 'author' );
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists( 'nexopos_orders_products_refunds' );
        Schema::dropIfExists( 'nexopos_orders_refunds' );
    }
}
