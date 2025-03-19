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
        Schema::create('nexopos_drivers_statuses', function (Blueprint $table) {
            $table->id();
            $table->integer( 'driver_id' );
            $table->enum( 'status', [ 'available', 'busy', 'offline', 'disabled' ] )->default( 'offline' );
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nexopos_drivers_statuses');
    }
};
