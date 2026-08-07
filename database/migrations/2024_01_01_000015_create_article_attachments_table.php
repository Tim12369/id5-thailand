<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('article_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->string('filename');
            $table->string('original_name');
            $table->string('path', 500);
            $table->string('disk', 50)->default('local');
            $table->string('mime_type', 100);
            $table->unsignedInteger('size_bytes');
            $table->smallInteger('sort_order')->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->index('article_id');
        });
    }
    public function down(): void { Schema::dropIfExists('article_attachments'); }
};
