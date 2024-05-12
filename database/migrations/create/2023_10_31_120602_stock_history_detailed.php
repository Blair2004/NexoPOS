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
        if ( ! Schema::hasTable( 'nexopos_products_histories_combined' ) ) {
            Schema::create( 'nexopos_products_histories_combined', function ( Blueprint $table ) {
                $table->increments( 'id' );
                $table->string( 'name' );
                $table->date( 'date' );
                $table->integer( 'product_id' );
                $table->integer( 'unit_id' );
                $table->float( 'initial_quantity' )->default( 0 );
                $table->float( 'sold_quantity' )->default( 0 );
                $table->float( 'procured_quantity' )->default( 0 );
                $table->float( 'defective_quantity' )->default( 0 );
                $table->float( 'final_quantity' )->default( 0 );
                $table->string( 'uuid' )->nullable();
                $table->timestamps();
            } );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists( 'nexopos_products_histories_combined' );
    }
};
