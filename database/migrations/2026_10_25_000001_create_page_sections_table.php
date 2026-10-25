<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Content editors compose themselves in WordPress's block editor: rows of
 * columns holding text, headings, pictures, PDF documents and buttons.
 *
 *  - kind "section": placed on an existing page of the website (`page`, the
 *    page's address, and `position`, where on it);
 *  - kind "page": a page of its own, at /page/{slug or number}.
 *
 * `body_html` is the finished HTML WordPress renders from the blocks, with its
 * pictures and documents already copied to this website.
 */
return new class extends Migration
{
    public function up(): void
    {
        if ($this->tableExists('page_sections')) {
            return;
        }

        Schema::create('page_sections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wp_post_id')->unique();
            $table->string('kind', 10);
            $table->string('page', 191)->nullable();
            $table->string('position', 30)->default('bottom');
            $table->string('slug', 191)->nullable()->index();
            $table->string('title_th')->nullable();
            $table->string('title_en')->nullable();
            $table->text('excerpt')->nullable();
            $table->longText('body_html');
            $table->string('language', 5)->default('both');
            $table->string('background', 10)->default('none');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_published')->default(false);
            $table->timestamp('cms_synced_at')->nullable();
            $table->timestamps();

            $table->index(['kind', 'page', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_sections');
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
