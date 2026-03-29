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
        if (!Schema::hasTable('nexopos_themes_menus')) {
            Schema::create('nexopos_themes_menus', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('identifier');
                $table->string('theme_namespace')->nullable();
                $table->timestamps();

                $table->index('identifier');
                $table->index('theme_namespace');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nexopos_themes_menus');
    }
};
