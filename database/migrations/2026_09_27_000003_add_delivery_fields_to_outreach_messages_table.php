<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outreach_messages', function (Blueprint $table) {
            // Antrean kirim terjadwal (status "queued").
            $table->timestamp('scheduled_at')->nullable()->after('status');
            $table->unsignedTinyInteger('attempts')->default(0)->after('scheduled_at');
            $table->text('last_error')->nullable()->after('attempts');

            // Message-ID email keluar, untuk mencocokkan balasan via IMAP.
            $table->string('message_id')->nullable()->after('last_error');
            $table->timestamp('replied_at')->nullable()->after('sent_at');

            // Follow-up otomatis menunjuk ke pesan pertamanya.
            $table->foreignId('followup_of_id')->nullable()->after('template_id')
                ->constrained('outreach_messages')->nullOnDelete();

            $table->index(['status', 'scheduled_at']);
            $table->index('message_id');
        });
    }

    public function down(): void
    {
        Schema::table('outreach_messages', function (Blueprint $table) {
            $table->dropForeign(['followup_of_id']);
            $table->dropIndex(['status', 'scheduled_at']);
            $table->dropIndex(['message_id']);
            $table->dropColumn(['scheduled_at', 'attempts', 'last_error', 'message_id', 'replied_at', 'followup_of_id']);
        });
    }
};
