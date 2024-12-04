<?php

use App\Jobs\RefreshOrderJob;
use App\Models\Order;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Order::get()->each( function ( $order ) {
            RefreshOrderJob::dispatch( $order );
        } );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
