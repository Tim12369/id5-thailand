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
        Schema::dropIfExists('document_versions');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('document_categories');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('document_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon', 50)->default('ti-file');
            $table->string('color', 20)->default('slate');
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('document_code', 30)->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('category_id')->constrained('document_categories')->cascadeOnDelete();
            $table->string('department');
            $table->string('author_name');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('version', 20)->default('1.0');
            $table->enum('status', ['draft', 'under_review', 'approved', 'archived'])->default('draft');
            $table->string('path', 500)->nullable();
            $table->string('disk', 50)->default('local');
            $table->unsignedInteger('file_size_bytes')->nullable();
            $table->unsignedInteger('access_count')->default(0);
            $table->timestamps();
            $table->index(['status', 'category_id', 'department']);
        });

        Schema::create('document_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->string('version', 20);
            $table->string('editor_name');
            $table->text('change_summary');
            $table->timestamp('created_at')->useCurrent();
        });
    }
};
