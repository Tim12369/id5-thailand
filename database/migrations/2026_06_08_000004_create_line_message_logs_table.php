<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('line_message_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('line_subscriber_id')->nullable()->constrained('line_subscribers')->nullOnDelete();
            $table->enum('direction', ['inbound', 'outbound']);
            $table->string('message_type');
            $table->json('payload');
            $table->string('related_type')->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->timestamps();
            $table->index(['related_type', 'related_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('line_message_logs'); }
};
