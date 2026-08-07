<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin-editable RESULT CONTENT for each inferred assessment status (the Hybrid
 * design: branching/scoring logic stays in config, human-readable guidance lives
 * here so officers can maintain it in the portal, in TH/EN/ZH).
 *
 * Deferred by product decision (avoids fabricated data): estimated_fees and
 * appointment_required are intentionally NOT columns yet.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_recommendations', function (Blueprint $table) {
            $table->id();
            $table->string('status_key', 60)->unique(); // matches config/assessment/statuses.php key
            $table->string('risk_level', 20)->default('info'); // info | low | medium | high -> result colour

            // Localized result content (nullable — officers fill in via the portal;
            // the result view degrades gracefully when a field is empty).
            foreach (['legal_basis', 'validity_note', 'allowed_activities', 'restricted_activities', 'deadlines', 'responsible_agency', 'important_notices'] as $field) {
                $table->text("{$field}_th")->nullable();
                $table->text("{$field}_en")->nullable();
                $table->text("{$field}_zh")->nullable();
            }

            // Deep-dive links into the existing Smart Guide (falls back to the
            // status's config defaults when left null).
            $table->string('suggested_persona', 40)->nullable();
            $table->json('suggested_topics')->nullable();

            $table->boolean('is_active')->default(true);
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_recommendations');
    }
};
