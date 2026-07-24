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
        ns()->option->set( 'ns_media_library_layout', 'modern' );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        ns()->option->set( 'ns_media_library_layout', 'legacy' );
    }
};
