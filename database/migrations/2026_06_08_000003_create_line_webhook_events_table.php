<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('line_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('line_event_id')->nullable()->unique();
            $table->string('event_hash', 64)->index();
            $table->string('line_user_id')->nullable()->index();
            $table->string('event_type')->index();
            $table->json('raw_payload');
            $table->enum('status', ['received', 'processing', 'processed', 'failed', 'ignored'])->default('received');
            $table->timestamp('processed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->string('reply_token')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('line_webhook_events'); }
};
