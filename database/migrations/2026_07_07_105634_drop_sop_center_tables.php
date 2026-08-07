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
        Schema::dropIfExists('sop_related');
        Schema::dropIfExists('sop_versions');
        Schema::dropIfExists('sop_documents');
        Schema::dropIfExists('sop_steps');
        Schema::dropIfExists('sops');
        Schema::dropIfExists('sop_categories');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('sop_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon', 50)->default('ti-file');
            $table->string('color', 20)->default('slate');
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();
        });

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

        Schema::create('sop_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sop_id')->constrained('sops')->cascadeOnDelete();
            $table->smallInteger('step_number');
            $table->string('title');
            $table->text('description')->nullable();
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('sop_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sop_id')->constrained('sops')->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_required')->default(true);
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('sop_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sop_id')->constrained('sops')->cascadeOnDelete();
            $table->string('version', 20);
            $table->string('editor_name');
            $table->text('change_summary');
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('sop_related', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sop_id')->constrained('sops')->cascadeOnDelete();
            $table->foreignId('related_sop_id')->constrained('sops')->cascadeOnDelete();
            $table->unique(['sop_id', 'related_sop_id']);
        });
    }
};
