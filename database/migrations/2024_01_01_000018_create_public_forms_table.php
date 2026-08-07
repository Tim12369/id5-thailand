<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('public_forms', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name_th');
            $table->string('name_en');
            $table->string('name_zh')->nullable();
            $table->text('description_th')->nullable();
            $table->text('description_en')->nullable();
            $table->text('description_zh')->nullable();
            $table->string('category', 100)->default('other');
            $table->string('path', 500);
            $table->string('disk', 50)->default('local');
            $table->string('version', 20)->default('1.0');
            $table->unsignedInteger('file_size_bytes')->nullable();
            $table->unsignedInteger('download_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['category', 'is_active']);
        });
    }
    public function down(): void { Schema::dropIfExists('public_forms'); }
};
