<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // General
            ['key' => 'general.site_name',      'value' => 'ID5 @ Thailand 5.0',  'type' => 'string', 'group' => 'general'],
            ['key' => 'general.emergency_phone', 'value' => '1178',                'type' => 'string', 'group' => 'general'],
            ['key' => 'general.ita_score',       'value' => '89.50',               'type' => 'string', 'group' => 'general'],
            ['key' => 'general.ita_year',        'value' => '2567',                'type' => 'string', 'group' => 'general'],
            ['key' => 'general.contact_email',   'value' => 'info@id5.go.th',      'type' => 'string', 'group' => 'general'],

            // Security
            ['key' => 'security.login_max_attempts', 'value' => '5',  'type' => 'integer', 'group' => 'security'],
            ['key' => 'security.lockout_minutes',     'value' => '15', 'type' => 'integer', 'group' => 'security'],
            ['key' => 'security.otp_ttl_minutes',     'value' => '5',  'type' => 'integer', 'group' => 'security'],
            ['key' => 'security.password_expiry_days','value' => '90', 'type' => 'integer', 'group' => 'security'],

            // Line OA
            ['key' => 'line_oa.enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'line_oa'],

            // Content
            ['key' => 'content.news_per_page',     'value' => '12', 'type' => 'integer', 'group' => 'content'],
            ['key' => 'content.kb_per_page',       'value' => '12', 'type' => 'integer', 'group' => 'content'],
            ['key' => 'content.search_max_results', 'value' => '5',  'type' => 'integer', 'group' => 'content'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(['key' => $setting['key']], $setting);
        }

        $this->command->info('✅ Settings seeded: ' . count($settings) . ' records');
    }
}
