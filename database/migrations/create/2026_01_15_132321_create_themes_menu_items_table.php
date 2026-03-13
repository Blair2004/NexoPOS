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
        if (!Schema::hasTable('nexopos_themes_menu_items')) {
            Schema::create('nexopos_themes_menu_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('menu_id');
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->string('title');
                $table->string('url');
                $table->string('target')->default('_self');
                $table->text('css_classes')->nullable();
                $table->integer('order')->default(0);
                $table->integer('depth')->default(0);
                $table->timestamps();

                $table->foreign('menu_id')->references('id')->on('nexopos_themes_menus')->onDelete('cascade');
                $table->foreign('parent_id')->references('id')->on('nexopos_themes_menu_items')->onDelete('cascade');

                $table->index('menu_id');
                $table->index('parent_id');
                $table->index('order');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nexopos_themes_menu_items');
    }
};
