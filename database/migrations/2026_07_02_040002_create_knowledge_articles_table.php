<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_articles');
    }
};
