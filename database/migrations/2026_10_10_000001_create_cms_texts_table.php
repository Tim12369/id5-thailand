<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Wording changed in WordPress for the fixed text of the website: menu labels,
 * headings, buttons, the service guides, the assessment questions.
 *
 * The built-in wording stays where it always was, in resources/lang. A row here
 * replaces one line of it in one language; removing the row brings the built-in
 * wording back. The table therefore only ever holds what an editor has actually
 * changed.
 */
return new class extends Migration
{
    public function up(): void
    {
        if ($this->tableExists('cms_texts')) {
            return;
        }

        Schema::create('cms_texts', function (Blueprint $table) {
            $table->id();
            $table->string('key', 191);
            $table->string('locale', 5);
            $table->text('value');
            $table->timestamps();

            $table->unique(['key', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_texts');
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
