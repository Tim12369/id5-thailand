<?php

namespace Database\Seeders;

use App\Models\AssessmentRecommendation;
use Illuminate\Database\Seeder;

class AssessmentRecommendationSeeder extends Seeder
{
    /**
     * Seeds one recommendation row per config-defined status. Only STRUCTURAL
     * fields are seeded (risk level, deep-dive persona/topics from config) —
     * the localized legal guidance text is intentionally left blank for officers
     * to author in the portal, so nothing legal is fabricated here.
     *
     * Uses firstOrCreate so re-running never clobbers officer-entered content.
     */
    public function run(): void
    {
        // Sensible default risk level per status (officers can adjust in-portal).
        $risk = [
            'thai_citizen' => 'info',
            'tourist' => 'low',
            'visa_exemption' => 'low',
            'visa_on_arrival' => 'low',
            'student' => 'low',
            'worker' => 'medium',
            'business' => 'medium',
            'investor' => 'low',
            'family' => 'low',
            'retirement' => 'low',
            'permanent_resident' => 'low',
            'stateless' => 'high',
            'refugee_protection' => 'high',
            'representative' => 'info',
            'employer' => 'medium',
            'landlord' => 'medium',
            'embassy_consular' => 'info',
            'emergency' => 'high',
        ];

        $sort = 0;
        foreach (config('assessment.statuses') as $key => $def) {
            AssessmentRecommendation::firstOrCreate(
                ['status_key' => $key],
                [
                    'risk_level' => $risk[$key] ?? 'info',
                    'suggested_persona' => $def['persona'] ?? null,
                    'suggested_topics' => $def['topics'] ?? [],
                    'is_active' => true,
                    'sort_order' => $sort++,
                ]
            );
        }

        $this->command->info('✅ Assessment recommendations seeded: ' . count(config('assessment.statuses')));
    }
}
