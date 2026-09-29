<?php

namespace App\Http\Controllers;

use App\Mail\EmailBrand;
use App\Models\BlacklistEntry;
use App\Models\OutreachMessage;

/**
 * Link "Berhenti berlangganan" di email outreach (URL bertanda tangan, tanpa login).
 * GET dari klik penerima, POST dari tombol one-click unsubscribe Gmail (RFC 8058).
 */
class UnsubscribeController extends Controller
{
    public function __invoke(OutreachMessage $outreachMessage)
    {
        $email = $outreachMessage->lead?->email;

        if ($email) {
            BlacklistEntry::add('email', $email, 'unsubscribe');

            // Batalkan email lain ke alamat ini yang masih menunggu antrean.
            OutreachMessage::where('type', 'email')
                ->where('status', 'queued')
                ->whereHas('lead', fn ($q) => $q->where('email', $email))
                ->update(['status' => 'failed', 'scheduled_at' => null, 'last_error' => 'Penerima berhenti berlangganan.']);

            if ($outreachMessage->lead->pipeline_stage !== 'deal') {
                $outreachMessage->lead->update(['pipeline_stage' => 'lost']);
            }
        }

        if (request()->isMethod('post')) {
            return response()->noContent();
        }

        return view('unsubscribe', [
            'email' => $email,
            'brand' => EmailBrand::fromSettings(),
        ]);
    }
}
