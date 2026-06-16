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
        if (!Schema::hasTable('nexopos_themes_pages')) {
            Schema::create('nexopos_themes_pages', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->json('content')->nullable();
                $table->enum('status', ['draft', 'published'])->default('draft');
                $table->string('theme_namespace')->nullable();
                $table->unsignedInteger('author_id');
                $table->timestamp('published_at')->nullable();
                $table->timestamps();

                $table->foreign('parent_id')->references('id')->on('nexopos_themes_pages')->onDelete('set null');
                $table->foreign('author_id')->references('id')->on('nexopos_users')->onDelete('cascade');

                $table->index('slug');
                $table->index('status');
                $table->index('theme_namespace');
                $table->index('author_id');
                $table->index('parent_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nexopos_themes_pages');
    }
};
