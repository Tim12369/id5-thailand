<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * An ITA topic can carry a picture the editor uploaded in WordPress, shown in
 * place of its icon. The file is mirrored onto the public disk like every
 * other picture; this column holds its path there.
 */
return new class extends Migration
{
    public function up(): void
    {
        if ($this->hasColumn()) {
            return;
        }

        Schema::table('ita_cards', function (Blueprint $table) {
            $table->string('icon_image')->nullable()->after('icon');
        });
    }

    public function down(): void
    {
        if (! $this->hasColumn()) {
            return;
        }

        Schema::table('ita_cards', function (Blueprint $table) {
            $table->dropColumn('icon_image');
        });
    }

    /**
     * On MySQL, asked with an ordinary SELECT rather than a metadata query: the
     * MariaDB build used for local development has crashed on the latter.
     *
     * Everywhere else the schema helper is used. A probe that fails on purpose
     * is not an option on PostgreSQL, where the migration runs in a transaction
     * and one failed statement aborts everything after it.
     */
    private function hasColumn(): bool
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return Schema::hasColumn('ita_cards', 'icon_image');
        }

        try {
            DB::select('select icon_image from ita_cards limit 1');

            return true;
        } catch (Throwable) {
            return false;
        }
    }
};
