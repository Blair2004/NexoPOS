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
        Schema::create('nexopos_orders_delivery_proof', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_delivered')->default(false);
            $table->text( 'note' )->nullable();
            $table->string('delivery_proof')->nullable();
            $table->boolean( 'paid_on_delivery' )->default(false);
            $table->integer('order_id');
            $table->integer('driver_id');
            $table->datetime('delivered_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nexopos_orders_delivery_proof');
    }
};
