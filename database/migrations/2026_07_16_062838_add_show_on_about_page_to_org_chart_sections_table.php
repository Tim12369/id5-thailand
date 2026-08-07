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
        Schema::table('org_chart_sections', function (Blueprint $table) {
            // Most sections (Police, Immigration Bureau, Division 5) show as
            // tabs on the About Us page. Sections that get their own
            // dedicated page instead (e.g. Immigration Technology Division)
            // are still managed through this same admin screen, but opt out.
            $table->boolean('show_on_about_page')->default(true)->after('theme');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('org_chart_sections', function (Blueprint $table) {
            $table->dropColumn('show_on_about_page');
        });
    }
};
