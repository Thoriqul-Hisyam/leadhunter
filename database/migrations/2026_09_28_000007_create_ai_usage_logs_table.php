<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Satu baris per panggilan ke provider AI (termasuk yang gagal), untuk memantau latensi & token.
        Schema::create('ai_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->string('feature', 30);
            $table->string('provider', 10); // primary | backup
            $table->string('model', 150)->nullable();
            $table->unsignedInteger('duration_ms');
            $table->unsignedInteger('prompt_tokens')->nullable();
            $table->unsignedInteger('completion_tokens')->nullable();
            $table->unsignedInteger('total_tokens')->nullable();
            $table->boolean('success');
            $table->string('error', 500)->nullable();
            $table->timestamp('created_at')->nullable()->index();

            $table->index(['feature', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usage_logs');
    }
};
