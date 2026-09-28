<?php

namespace App\Services\WhatsApp;

use Illuminate\Http\Request;

/**
 * Mode manual: pengguna mengirim sendiri lewat link wa.me (click-to-chat).
 */
class ManualGateway implements WhatsAppGateway
{
    public function name(): string
    {
        return 'manual';
    }

    public function isAutomatic(): bool
    {
        return false;
    }

    public function send(string $number, string $message): WhatsAppResult
    {
        return WhatsAppResult::failed('WhatsApp gateway belum diatur (mode manual). Kirim lewat tombol WhatsApp, atau pilih Fonnte/Wablas di Pengaturan.');
    }

    public function parseWebhook(Request $request): array
    {
        return [];
    }
}
