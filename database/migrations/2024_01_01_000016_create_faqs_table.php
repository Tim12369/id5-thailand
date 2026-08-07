<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->text('question_th');
            $table->text('question_en')->nullable();
            $table->text('question_zh')->nullable();
            $table->longText('answer_th');
            $table->longText('answer_en')->nullable();
            $table->longText('answer_zh')->nullable();
            $table->boolean('is_public')->default(true);
            $table->boolean('is_active')->default(true);
            $table->smallInteger('sort_order')->default(0);
            $table->unsignedInteger('view_count')->default(0);
            $table->timestamps();
            $table->index(['is_public', 'is_active']);
        });
    }
    public function down(): void { Schema::dropIfExists('faqs'); }
};
