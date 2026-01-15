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
        if (!Schema::hasTable('nexopos_themes_slugs')) {
            Schema::create('nexopos_themes_slugs', function (Blueprint $table) {
                $table->id();
                $table->string('feature');
                $table->string('slug');
                $table->string('theme_namespace')->nullable();
                $table->timestamps();

                $table->index('feature');
                $table->index('slug');
                $table->index('theme_namespace');
                $table->unique(['feature', 'theme_namespace']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nexopos_themes_slugs');
    }
};
