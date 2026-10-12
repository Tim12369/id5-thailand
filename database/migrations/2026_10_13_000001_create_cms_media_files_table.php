<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A copy, kept in the database, of every file mirrored from WordPress.
 *
 * Only used on hosts whose disk does not survive a restart (CMS_MEDIA_PERSIST):
 * there the pictures copied into storage/ would vanish each time the service
 * restarts, so they are put back from here when it starts. The content is
 * stored as base64 text, which every database driver handles the same way.
 */
return new class extends Migration
{
    public function up(): void
    {
        if ($this->tableExists('cms_media_files')) {
            return;
        }

        Schema::create('cms_media_files', function (Blueprint $table) {
            $table->id();
            $table->string('disk', 50);
            $table->string('path', 191);
            $table->string('mime', 100)->nullable();
            $table->unsignedInteger('size');
            $table->longText('content');
            $table->timestamps();

            $table->unique(['disk', 'path']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_media_files');
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
