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
        if ( ! Schema::hasTable( 'nexopos_products_adjustments' ) ) {
            Schema::create( 'nexopos_products_adjustments', function ( Blueprint $table ) {
                $table->id();
                $table->integer( 'author_id' );
                $table->string( 'title' )->nullable();
                $table->enum( 'status', [ 'draft', 'performed' ] )->default( 'draft' );
                $table->text( 'description' )->nullable();
                $table->timestamps();
            } );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists( 'nexopos_products_adjustments' );
    }
};
