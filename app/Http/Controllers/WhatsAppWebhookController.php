<?php

namespace App\Http\Controllers;

use App\Services\Replies\WhatsAppReplyHandler;
use App\Services\WhatsApp\WhatsAppManager;
use Illuminate\Http\Request;

/**
 * Endpoint webhook untuk gateway WhatsApp (balasan masuk & status kirim).
 * URL mengandung token rahasia dari Pengaturan: /webhooks/whatsapp/{token}.
 */
class WhatsAppWebhookController extends Controller
{
    public function __invoke(Request $request, string $token, WhatsAppManager $whatsapp, WhatsAppReplyHandler $handler)
    {
        $expected = (string) config('leadhunter.whatsapp.webhook_token');

        abort_if($expected === '' || ! hash_equals($expected, $token), 404);

        // Beberapa gateway memverifikasi URL dengan GET sebelum menyimpannya.
        if ($request->isMethod('get')) {
            return response()->json(['status' => 'ok']);
        }

        $result = $handler->handle($whatsapp->driver()->parseWebhook($request));

        return response()->json(['status' => 'ok'] + $result);
    }
}
