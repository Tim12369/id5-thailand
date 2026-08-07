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
        Schema::dropIfExists('pages');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title_th');
            $table->string('title_en')->nullable();
            $table->longText('body_th')->nullable();
            $table->longText('body_en')->nullable();
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }
};
