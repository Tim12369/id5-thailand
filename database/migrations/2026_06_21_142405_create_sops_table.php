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
        Schema::create('sops', function (Blueprint $table) {
            $table->id();
            $table->string('sop_code', 30)->unique();
            $table->string('title');
            $table->text('purpose');
            $table->text('scope')->nullable();
            $table->foreignId('category_id')->constrained('sop_categories')->cascadeOnDelete();
            $table->string('department');
            $table->string('version', 20)->default('1.0');
            $table->enum('status', ['draft', 'under_review', 'active', 'archived'])->default('draft');
            $table->date('effective_date')->nullable();
            $table->date('review_date')->nullable();
            $table->json('approval_chain')->nullable();
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
        Schema::dropIfExists('sops');
    }
};
