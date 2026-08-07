<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extends Station (the existing, single source of truth for immigration
 * offices — used by the public Office Locator, Command Center, and Smart
 * Guide) with the additional public-facing fields requested for the office
 * directory, rather than introducing a second parallel office table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stations', function (Blueprint $table) {
            $table->string('facebook_url')->nullable()->after('website');
            $table->string('line_url')->nullable()->after('facebook_url');
            // Optional manual override; falls back to a lat/lng-derived Google
            // Maps link (Station::getGoogleMapsUrlAttribute) when left blank.
            $table->string('google_maps_url')->nullable()->after('line_url');
            $table->string('image_path')->nullable()->after('google_maps_url');
            $table->string('parent_organization')->nullable()->after('image_path');
            $table->boolean('online_services_available')->default(false)->after('parent_organization');
            $table->date('last_verified_at')->nullable()->after('online_services_available');
        });
    }

    public function down(): void
    {
        Schema::table('stations', function (Blueprint $table) {
            $table->dropColumn([
                'facebook_url', 'line_url', 'google_maps_url', 'image_path',
                'parent_organization', 'online_services_available', 'last_verified_at',
            ]);
        });
    }
};
