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
        Schema::create('sop_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sop_id')->constrained('sops')->cascadeOnDelete();
            $table->string('version', 20);
            $table->string('editor_name');
            $table->text('change_summary');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sop_versions');
    }
};
