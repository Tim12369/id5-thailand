<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Removes the Internal Portal's own media library.
 *
 * It is superseded by the WordPress Media Library, which is where images and
 * documents are now uploaded and picked. Dropping it is safe because it was
 * never populated — the tables held no rows at the time of the migration, and
 * nothing outside the removed admin screens ever read them. Files on disk are
 * untouched; only the empty index tables go.
 *
 * The `up()` refuses to run if any row has appeared since, so an environment
 * that did use the library keeps its data and reports the problem instead of
 * losing it silently.
 */
return new class extends Migration
{
    public function up(): void
    {
        $tables = ['media_media_tag', 'media', 'media_tags', 'media_folders'];

        foreach (['media', 'media_folders', 'media_tags'] as $table) {
            if (! $this->tableExists($table)) {
                continue;
            }

            $count = DB::table($table)->count();

            if ($count > 0) {
                throw new RuntimeException(
                    "Refusing to drop `{$table}`: it holds {$count} row(s). The custom media "
                    ."library was expected to be empty. Export its contents into the WordPress "
                    ."Media Library first, then re-run this migration."
                );
            }
        }

        // Pivot first, then the tables it points at.
        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }
    }

    /**
     * Recreate the tables exactly as they were, so a rollback restores the
     * schema the removed screens expected. They come back empty, which is the
     * state they were in.
     */
    public function down(): void
    {
        Schema::create('media_folders', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('parent_id')->nullable()->constrained('media_folders')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('folder_id')->nullable()->constrained('media_folders')->nullOnDelete();
            $table->string('disk', 50)->default('public');
            $table->string('path', 500);
            $table->string('original_name');
            $table->string('mime_type', 150);
            $table->unsignedBigInteger('size_bytes');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('alt_text')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('mime_type');
        });

        Schema::create('media_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('media_media_tag', function (Blueprint $table) {
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            $table->foreignId('media_tag_id')->constrained('media_tags')->cascadeOnDelete();
            $table->primary(['media_id', 'media_tag_id']);
        });
    }

    /**
     * `SHOW TABLES` rather than Schema::hasTable(): the helper queries
     * information_schema through a prepared statement, which crashes the
     * MariaDB 10.4 build shipped with the XAMPP used for local development.
     */
    private function tableExists(string $table): bool
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return Schema::hasTable($table);
        }

        return DB::select('SHOW TABLES LIKE '.DB::getPdo()->quote($table)) !== [];
    }
};
