<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('type', 50);
            $table->string('name_th');
            $table->string('name_en')->nullable();
            $table->string('name_zh')->nullable();
            $table->string('slug');
            $table->text('description_th')->nullable();
            $table->string('icon', 100)->nullable();
            $table->string('color', 20)->nullable();
            $table->smallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['type', 'slug']);
            $table->index('type');
            $table->index('is_active');
        });
    }
    public function down(): void { Schema::dropIfExists('categories'); }
};
