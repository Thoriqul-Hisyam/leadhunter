<?php

namespace App\Services\WhatsApp;

use App\Helpers\Phone;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Wablas (wablas.com). Setiap akun memakai domain server sendiri, mis. https://tegal.wablas.com.
 * Kirim: POST {base}/api/send-message (header Authorization: <token>, field phone & message).
 * Webhook: JSON berisi phone & message (pesan masuk) atau id & status (status kirim).
 */
class WablasGateway implements WhatsAppGateway
{
    public function __construct(protected ?string $token, protected ?string $baseUrl)
    {
    }

    public function name(): string
    {
        return 'wablas';
    }

    public function isAutomatic(): bool
    {
        return true;
    }

    public function send(string $number, string $message): WhatsAppResult
    {
        if (! $this->token || ! $this->baseUrl) {
            return WhatsAppResult::failed('Token dan URL server Wablas belum diisi di Pengaturan.');
        }

        try {
            $response = Http::withHeaders(['Authorization' => $this->token])
                ->asForm()
                ->timeout(30)
                ->post(rtrim($this->baseUrl, '/').'/api/send-message', [
                    'phone' => $number,
                    'message' => $message,
                ]);
        } catch (ConnectionException $e) {
            return WhatsAppResult::failed('Tidak bisa terhubung ke Wablas: '.$e->getMessage());
        }

        $json = $response->json() ?? [];

        if ($response->successful() && ($json['status'] ?? false) === true) {
            return WhatsAppResult::sent((string) ($json['data']['messages'][0]['id'] ?? $json['data']['id'] ?? ''));
        }

        $reason = (string) ($json['message'] ?? $response->body());

        return WhatsAppResult::failed('Wablas: '.Str::limit($reason, 200), FonnteGateway::isNumberProblem($reason));
    }

    public function parseWebhook(Request $request): array
    {
        $data = $request->all();

        if (! empty($data['phone']) && isset($data['message']) && empty($data['isFromMe'])) {
            return [[
                'type' => 'message',
                'from' => Phone::toWhatsApp((string) $data['phone']) ?? (string) $data['phone'],
                'text' => (string) $data['message'],
            ]];
        }

        if (! empty($data['id']) && ! empty($data['status'])) {
            $status = strtolower((string) $data['status']);

            return [[
                'type' => 'status',
                'id' => (string) $data['id'],
                'status' => $status,
                'failed' => in_array($status, ['failed', 'cancel', 'rejected', 'error'], true),
            ]];
        }

        return [];
    }
}
