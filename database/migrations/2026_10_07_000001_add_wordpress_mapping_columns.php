<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Links each content row to the WordPress post that now owns its editing.
 *
 * After the migration to WordPress these tables become a derived read model:
 * the public site still reads them, but they are written only by the CMS sync
 * (see App\Services\Cms). `wp_post_id` is how an incoming change finds the row
 * it belongs to, which is what makes re-running a sync idempotent rather than
 * duplicating content.
 *
 * Nullable throughout, so rows that predate the migration — or that are managed
 * outside WordPress, such as stations created by the CSV import — stay valid.
 *
 * Existence checks use `SHOW TABLES` / `SHOW COLUMNS` rather than
 * Schema::hasTable()/hasColumn(). Those helpers query information_schema
 * through a server-side prepared statement, which segfaults the MariaDB 10.4
 * build shipped with the XAMPP used for local development; `SHOW` returns the
 * same answer without touching information_schema.
 */
return new class extends Migration
{
    /**
     * Tables that gain a WordPress post mapping.
     */
    private const TABLES = [
        'articles',
        'faqs',
        'public_forms',
        'stations',
        'org_chart_members',
        'ita_cards',
        'home_division_cards',
        'guide_topics',
        'assessment_recommendations',
    ];

    /**
     * Grouping tables whose rows come from WordPress taxonomy terms.
     */
    private const TERM_TABLES = [
        'categories',
        'tags',
        'org_chart_sections',
        'ita_sections',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            if (! $this->tableExists($table) || $this->columnExists($table, 'wp_post_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) {
                $t->unsignedBigInteger('wp_post_id')->nullable()->unique();
                $t->timestamp('cms_synced_at')->nullable();
            });
        }

        foreach (self::TERM_TABLES as $table) {
            if (! $this->tableExists($table) || $this->columnExists($table, 'wp_term_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) {
                $t->unsignedBigInteger('wp_term_id')->nullable()->index();
            });
        }

        // Org chart tiers are rebuilt from each person's tier label rather than
        // mapped one-to-one, so they get a stable key instead of a post ID.
        if ($this->tableExists('org_chart_tiers') && ! $this->columnExists('org_chart_tiers', 'cms_key')) {
            Schema::table('org_chart_tiers', function (Blueprint $t) {
                $t->string('cms_key', 191)->nullable()->index();
            });
        }

        if (! $this->tableExists('cms_sync_state')) {
            Schema::create('cms_sync_state', function (Blueprint $t) {
                $t->id();
                // What this row records: 'pull', 'push' or 'media'.
                $t->string('kind', 20);
                // Scope within that kind, e.g. a post type or a source file path.
                $t->string('scope', 255);
                $t->string('reference', 255)->nullable();
                $t->timestamp('last_run_at')->nullable();
                $t->string('status', 20)->default('ok');
                $t->text('message')->nullable();
                $t->timestamps();

                $t->unique(['kind', 'scope']);
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            if (! $this->tableExists($table) || ! $this->columnExists($table, 'wp_post_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) use ($table) {
                $t->dropUnique($table.'_wp_post_id_unique');
                $t->dropColumn(['wp_post_id', 'cms_synced_at']);
            });
        }

        foreach (self::TERM_TABLES as $table) {
            if ($this->tableExists($table) && $this->columnExists($table, 'wp_term_id')) {
                Schema::table($table, fn (Blueprint $t) => $t->dropColumn('wp_term_id'));
            }
        }

        if ($this->tableExists('org_chart_tiers') && $this->columnExists('org_chart_tiers', 'cms_key')) {
            Schema::table('org_chart_tiers', fn (Blueprint $t) => $t->dropColumn('cms_key'));
        }

        Schema::dropIfExists('cms_sync_state');
    }

    private function tableExists(string $table): bool
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return Schema::hasTable($table);
        }

        return DB::select('SHOW TABLES LIKE '.DB::getPdo()->quote($table)) !== [];
    }

    private function columnExists(string $table, string $column): bool
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return Schema::hasColumn($table, $column);
        }

        $quoted = str_replace('`', '``', $table);

        return DB::select("SHOW COLUMNS FROM `{$quoted}` LIKE ".DB::getPdo()->quote($column)) !== [];
    }
};
