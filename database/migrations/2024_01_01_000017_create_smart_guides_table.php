<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('smart_guides', function (Blueprint $table) {
            $table->id();
            $table->string('persona', 50);
            $table->string('topic', 100);
            $table->enum('dimension', ['who','what','why','where','when','how']);
            $table->longText('content_th');
            $table->longText('content_en')->nullable();
            $table->longText('content_zh')->nullable();
            $table->smallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['persona', 'topic', 'dimension']);
            $table->index(['persona', 'topic']);
        });
    }
    public function down(): void { Schema::dropIfExists('smart_guides'); }
};
