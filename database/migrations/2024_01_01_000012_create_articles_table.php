<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['news','announcement','alert','kb_public','kb_internal','sop','best_practice','km']);
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title_th', 500);
            $table->string('title_en', 500)->nullable();
            $table->string('title_zh', 500)->nullable();
            $table->string('slug', 500)->unique();
            $table->text('excerpt_th')->nullable();
            $table->text('excerpt_en')->nullable();
            $table->text('excerpt_zh')->nullable();
            $table->longText('body_th');
            $table->longText('body_en')->nullable();
            $table->longText('body_zh')->nullable();
            $table->string('thumbnail', 500)->nullable();
            $table->enum('status', ['draft','review','published','archived'])->default('draft');
            $table->boolean('is_public')->default(false);
            $table->boolean('is_pinned')->default(false);
            $table->enum('alert_level', ['info','warning','danger'])->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->unsignedInteger('view_count')->default(0);
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 500)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['type', 'status']);
            $table->index(['status', 'is_public']);
            $table->index('published_at');
            $table->index('is_pinned');
        });
    }
    public function down(): void { Schema::dropIfExists('articles'); }
};
