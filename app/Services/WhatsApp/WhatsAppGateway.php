<?php

namespace App\Services\WhatsApp;

use Illuminate\Http\Request;

/**
 * Penyedia pengiriman WhatsApp (Fonnte, Wablas, atau manual click-to-chat).
 */
interface WhatsAppGateway
{
    public function name(): string;

    /**
     * False untuk mode manual: pesan dikirim sendiri oleh pengguna lewat wa.me.
     */
    public function isAutomatic(): bool;

    /**
     * @param  string  $number  format internasional tanpa tanda plus, mis. 628123456789
     */
    public function send(string $number, string $message): WhatsAppResult;

    /**
     * Ubah payload webhook penyedia menjadi daftar event yang seragam.
     *
     * @return array<int, array{type: 'message', from: string, text: string}|array{type: 'status', id: string, status: string, failed: bool}>
     */
    public function parseWebhook(Request $request): array;
}
