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
        Schema::create('sop_related', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sop_id')->constrained('sops')->cascadeOnDelete();
            $table->foreignId('related_sop_id')->constrained('sops')->cascadeOnDelete();
            $table->unique(['sop_id', 'related_sop_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sop_related');
    }
};
