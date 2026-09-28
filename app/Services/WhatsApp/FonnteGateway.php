<?php

namespace App\Services\WhatsApp;

use App\Helpers\Phone;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Fonnte (fonnte.com): gateway WhatsApp populer di Indonesia.
 * Kirim: POST https://api.fonnte.com/send (header Authorization: <token>, field target & message).
 * Webhook pesan masuk: field sender & message. Webhook status: field id & status.
 */
class FonnteGateway implements WhatsAppGateway
{
    public const ENDPOINT = 'https://api.fonnte.com/send';

    public function __construct(protected ?string $token)
    {
    }

    public function name(): string
    {
        return 'fonnte';
    }

    public function isAutomatic(): bool
    {
        return true;
    }

    public function send(string $number, string $message): WhatsAppResult
    {
        if (! $this->token) {
            return WhatsAppResult::failed('Token Fonnte belum diisi di Pengaturan.');
        }

        try {
            $response = Http::withHeaders(['Authorization' => $this->token])
                ->asForm()
                ->timeout(30)
                ->post(self::ENDPOINT, [
                    'target' => $number,
                    'message' => $message,
                    'countryCode' => '62',
                ]);
        } catch (ConnectionException $e) {
            return WhatsAppResult::failed('Tidak bisa terhubung ke Fonnte: '.$e->getMessage());
        }

        $json = $response->json() ?? [];

        if ($response->successful() && ($json['status'] ?? false) === true) {
            $id = $json['id'] ?? null;

            return WhatsAppResult::sent(is_array($id) ? (string) ($id[0] ?? '') : (string) $id);
        }

        $reason = (string) ($json['reason'] ?? $json['detail'] ?? $response->body());

        return WhatsAppResult::failed('Fonnte: '.Str::limit($reason, 200), static::isNumberProblem($reason));
    }

    public function parseWebhook(Request $request): array
    {
        $data = $request->all();

        if (! empty($data['sender']) && isset($data['message'])) {
            return [[
                'type' => 'message',
                'from' => Phone::toWhatsApp((string) $data['sender']) ?? (string) $data['sender'],
                'text' => (string) $data['message'],
            ]];
        }

        if (! empty($data['id']) && ! empty($data['status'])) {
            $status = strtolower((string) $data['status']);

            return [[
                'type' => 'status',
                'id' => (string) $data['id'],
                'status' => $status,
                'failed' => in_array($status, ['failed', 'invalid', 'expired', 'error'], true),
            ]];
        }

        return [];
    }

    public static function isNumberProblem(string $reason): bool
    {
        return (bool) preg_match('/invalid|not registered|tidak terdaftar|not on whatsapp|unregistered/i', $reason);
    }
}
