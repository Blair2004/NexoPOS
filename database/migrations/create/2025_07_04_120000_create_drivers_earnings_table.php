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
        Schema::create('nexopos_drivers_earnings', function (Blueprint $table) {
            $table->id();
            $table->integer('driver_id');
            $table->integer('order_id');
            $table->enum('payment_type', ['fixed', 'percentage'])->default('fixed');
            $table->decimal('delivery_fee', 18, 5)->default(0);
            $table->decimal('rate_value', 18, 5)->default(0); // Fixed amount or percentage rate
            $table->decimal('earning_amount', 18, 5)->default(0); // Calculated earning
            $table->enum('status', ['pending', 'paid', 'cancelled'])->default('pending');
            $table->timestamp('delivery_date')->nullable();
            $table->timestamp('paid_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('driver_id');
            $table->index('order_id');
            $table->index('status');
            $table->index('delivery_date');
            
            // Foreign key constraints (if needed)
            // $table->foreign('driver_id')->references('id')->on('nexopos_users');
            // $table->foreign('order_id')->references('id')->on('nexopos_orders');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nexopos_drivers_earnings');
    }
};
