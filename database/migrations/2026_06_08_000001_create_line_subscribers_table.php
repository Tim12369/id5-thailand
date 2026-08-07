<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('line_subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('line_user_id')->unique();
            $table->string('display_name')->nullable();
            $table->string('picture_url')->nullable();
            $table->string('status_message')->nullable();
            $table->string('line_locale', 10)->nullable();
            $table->enum('preferred_locale', ['th', 'en', 'zh'])->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['active', 'blocked', 'unfollowed'])->default('active');
            $table->timestamp('followed_at')->nullable();
            $table->timestamp('unfollowed_at')->nullable();
            $table->timestamp('last_interacted_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index('status');
        });
    }
    public function down(): void { Schema::dropIfExists('line_subscribers'); }
};
