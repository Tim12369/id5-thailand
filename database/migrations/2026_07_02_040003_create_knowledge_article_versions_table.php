<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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

    public function down(): void
    {
        Schema::dropIfExists('knowledge_article_versions');
    }
};
