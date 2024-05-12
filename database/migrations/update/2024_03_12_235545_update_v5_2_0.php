<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        ns()->envEditor->set( 'REVERB_APP_ID', 'app-key-' . Str::random( 10 ) );
        ns()->envEditor->set( 'REVERB_APP_KEY', 'app-key-' . Str::random( 10 ) );
        ns()->envEditor->set( 'REVERB_APP_SECRET', Str::uuid() );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
