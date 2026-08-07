<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('line_conversation_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('line_subscriber_id')->constrained('line_subscribers')->cascadeOnDelete();
            $table->string('context')->default('idle');
            $table->json('state')->nullable();
            $table->string('last_intent')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();
            $table->index(['line_subscriber_id', 'expires_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('line_conversation_sessions'); }
};
