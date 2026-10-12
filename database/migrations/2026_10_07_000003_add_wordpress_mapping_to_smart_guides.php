<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Links Smart Guide guidance rows to the WordPress entry that owns them.
 *
 * Unlike the other content tables the link is not unique: one WordPress entry
 * ("tourist + visa") holds all six Who / What / Why / Where / When / How
 * answers, so up to six rows share one `wp_post_id`. It is what lets an entry
 * deleted in WordPress be found and hidden here.
 */
return new class extends Migration
{
    public function up(): void
    {
        if ($this->columnExists('smart_guides', 'wp_post_id')) {
            return;
        }

        Schema::table('smart_guides', function (Blueprint $table) {
            $table->unsignedBigInteger('wp_post_id')->nullable()->index();
            $table->timestamp('cms_synced_at')->nullable();
        });
    }

    public function down(): void
    {
        if (! $this->columnExists('smart_guides', 'wp_post_id')) {
            return;
        }

        Schema::table('smart_guides', function (Blueprint $table) {
            $table->dropIndex(['wp_post_id']);
            $table->dropColumn(['wp_post_id', 'cms_synced_at']);
        });
    }

    /**
     * `SHOW COLUMNS` rather than Schema::hasColumn(): the helper queries
     * information_schema through a prepared statement, which crashes the
     * MariaDB 10.4 build shipped with the XAMPP used for local development.
     */
    private function columnExists(string $table, string $column): bool
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return Schema::hasColumn($table, $column);
        }

        return DB::select("SHOW COLUMNS FROM `{$table}` LIKE ".DB::getPdo()->quote($column)) !== [];
    }
};
