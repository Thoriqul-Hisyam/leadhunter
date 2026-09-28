<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Timezone aplikasi berubah dari UTC ke APP_TIMEZONE (default Asia/Jakarta).
 * Timestamp yang sudah tersimpan ditulis dalam UTC, jadi digeser ke timezone baru
 * agar data lama tidak tampil 7 jam lebih awal.
 */
return new class extends Migration
{
    protected array $tables = [
        'users' => ['email_verified_at', 'created_at', 'updated_at'],
        'leads' => ['created_at', 'updated_at'],
        'lead_notes' => ['created_at', 'updated_at'],
        'campaigns' => ['created_at', 'updated_at'],
        'outreach_messages' => ['scheduled_at', 'sent_at', 'replied_at', 'created_at', 'updated_at'],
        'message_templates' => ['created_at', 'updated_at'],
        'scraping_notifications' => ['created_at', 'updated_at'],
        'blacklist_entries' => ['created_at', 'updated_at'],
        'settings' => ['created_at', 'updated_at'],
        'roles' => ['created_at', 'updated_at'],
        'permissions' => ['created_at', 'updated_at'],
        'failed_jobs' => ['failed_at'],
    ];

    public function up(): void
    {
        $this->shift(+1);
    }

    public function down(): void
    {
        $this->shift(-1);
    }

    protected function shift(int $direction): void
    {
        $offset = (new DateTime('now', new DateTimeZone(config('app.timezone'))))->getOffset() * $direction;

        if ($offset === 0) {
            return;
        }

        $driver = DB::getDriverName();

        foreach ($this->tables as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    continue;
                }

                $expression = match ($driver) {
                    'mysql', 'mariadb' => "DATE_ADD(`{$column}`, INTERVAL {$offset} SECOND)",
                    'sqlite' => "datetime(\"{$column}\", '".($offset >= 0 ? '+' : '')."{$offset} seconds')",
                    'pgsql' => "\"{$column}\" + interval '{$offset} seconds'",
                    default => null,
                };

                if ($expression) {
                    DB::table($table)->whereNotNull($column)->update([$column => DB::raw($expression)]);
                }
            }
        }
    }
};
