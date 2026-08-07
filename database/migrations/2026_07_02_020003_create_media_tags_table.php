<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('media_media_tag', function (Blueprint $table) {
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            $table->foreignId('media_tag_id')->constrained('media_tags')->cascadeOnDelete();
            $table->primary(['media_id', 'media_tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_media_tag');
        Schema::dropIfExists('media_tags');
    }
};
