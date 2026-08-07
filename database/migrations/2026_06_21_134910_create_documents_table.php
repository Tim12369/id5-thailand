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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
