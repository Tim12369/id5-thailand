<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('stations', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name_th');
            $table->string('name_en');
            $table->string('name_zh')->nullable();
            $table->string('province', 100);
            $table->string('region', 100)->default('ภาคเหนือ');
            $table->text('address_th')->nullable();
            $table->text('address_en')->nullable();
            $table->text('address_zh')->nullable();
            $table->string('phone', 100)->nullable();
            $table->string('email')->nullable();
            $table->decimal('lat', 10, 8)->nullable();
            $table->decimal('lng', 11, 8)->nullable();
            $table->json('opening_hours')->nullable();
            $table->boolean('is_active')->default(true);
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index('province');
            $table->index('is_active');
        });
    }
    public function down(): void { Schema::dropIfExists('stations'); }
};
