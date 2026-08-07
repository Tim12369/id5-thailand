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
        Schema::dropIfExists('knowledge_article_versions');
        Schema::dropIfExists('knowledge_articles');
        Schema::dropIfExists('knowledge_categories');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('knowledge_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon', 100)->nullable();
            $table->string('color', 20)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('knowledge_articles', function (Blueprint $table) {
            $table->id();
            $table->string('article_code')->unique();
            $table->enum('type', ['best_practice', 'manual', 'regulation', 'article', 'faq']);
            $table->foreignId('category_id')->nullable()->constrained('knowledge_categories')->nullOnDelete();
            $table->string('title_th');
            $table->string('title_en')->nullable();
            $table->longText('body_th')->nullable();
            $table->longText('body_en')->nullable();
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->string('version', 20)->default('1.0');
            $table->unsignedBigInteger('access_count')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['type', 'status']);
        });

        Schema::create('knowledge_article_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('knowledge_article_id')->constrained('knowledge_articles')->cascadeOnDelete();
            $table->string('version', 20);
            $table->string('editor_name')->nullable();
            $table->string('change_summary')->nullable();
            $table->longText('body_th_snapshot')->nullable();
            $table->longText('body_en_snapshot')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }
};
