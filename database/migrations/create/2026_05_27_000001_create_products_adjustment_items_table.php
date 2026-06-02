<?php

use App\Classes\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if ( ! Schema::hasTable( 'nexopos_products_adjustment_items' ) ) {
            Schema::create( 'nexopos_products_adjustment_items', function ( Blueprint $table ) {
                $table->id();
                $table->integer( 'adjustment_id' );
                $table->integer( 'product_id' );
                $table->string( 'product_name' );
                $table->integer( 'unit_id' );
                $table->string( 'unit_name' );
                $table->decimal( 'unit_price', 18, 5 )->default( 0 );
                $table->float( 'quantity' )->default( 0 );
                $table->string( 'adjust_action' );
                $table->text( 'description' )->nullable();
                $table->integer( 'procurement_product_id' )->nullable();
                $table->timestamps();
            } );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists( 'nexopos_products_adjustment_items' );
    }
};
