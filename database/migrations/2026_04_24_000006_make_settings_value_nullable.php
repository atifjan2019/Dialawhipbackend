<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Allow settings.value to be NULL so that "cleared" settings (e.g. an unset
 * logo URL or an empty social link) can be stored without violating the
 * NOT NULL constraint that the original create migration declared.
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        // doctrine/dbal is not a required dep on Laravel 11, so use raw SQL
        // per driver rather than Schema::change() with ->nullable().
        match ($driver) {
            'mysql' => DB::statement('ALTER TABLE `settings` MODIFY `value` JSON NULL'),
            'pgsql' => DB::statement('ALTER TABLE "settings" ALTER COLUMN "value" DROP NOT NULL'),
            'sqlite' => $this->sqliteRebuild(),
            default => Schema::table('settings', fn (Blueprint $t) => $t->json('value')->nullable()->change()),
        };
    }

    public function down(): void
    {
        // Best-effort revert. Does nothing on SQLite.
        $driver = DB::getDriverName();
        match ($driver) {
            'mysql' => DB::statement('ALTER TABLE `settings` MODIFY `value` JSON NOT NULL'),
            'pgsql' => DB::statement('ALTER TABLE "settings" ALTER COLUMN "value" SET NOT NULL'),
            default => null,
        };
    }

    private function sqliteRebuild(): void
    {
        // SQLite can't ALTER COLUMN; rebuild the table.
        DB::statement('CREATE TABLE settings_new (
            key VARCHAR(255) PRIMARY KEY,
            value TEXT NULL,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )');
        DB::statement('INSERT INTO settings_new (key, value, updated_at) SELECT key, value, updated_at FROM settings');
        DB::statement('DROP TABLE settings');
        DB::statement('ALTER TABLE settings_new RENAME TO settings');
    }
};
