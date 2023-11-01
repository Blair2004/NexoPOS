<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('nexopos_products_detailed_history', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->date( 'date' );
            $table->integer('product_id');
            $table->integer('unit_id');
            $table->float('initial_quantity');
            $table->float('sold_quantity');
            $table->float( 'procured_quantity' );
            $table->float('defective_quantity');
            $table->float('final_quantity');
            $table->integer('author');
            $table->string('uuid');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nexopos_products_detailed_history');
    }
};