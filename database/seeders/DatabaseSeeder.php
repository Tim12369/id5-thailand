<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            StationSeeder::class,
            UserSeeder::class,
            SettingSeeder::class,
            CategorySeeder::class,
            ItaSeeder::class,
            PublicFormSeeder::class,
            SmartGuideSeeder::class,
            GuideTopicSeeder::class,
            FaqSeeder::class,
            ArticleSeeder::class,
            DashboardSeeder::class,
            CommandCenterSeeder::class,
            CctvAuditSeeder::class,
            OrgChartSeeder::class,
            HomeDivisionCardSeeder::class,
            AssessmentRecommendationSeeder::class,
        ]);
    }
}
