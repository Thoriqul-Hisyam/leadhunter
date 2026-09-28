<?php

namespace App\Services\Replies;

use App\Exceptions\AiException;
use App\Models\OutreachMessage;
use App\Models\ScrapingNotification;
use App\Services\AiService;

/**
 * Kelompokkan balasan: tertarik / tanya harga / tidak tertarik / balasan otomatis / lainnya.
 * Urutan: aturan pasti untuk balasan otomatis → AI (jika tersambung) → kata kunci.
 */
class ReplyClassifier
{
    public function __construct(protected AiService $ai)
    {
    }

    public function classify(?string $subject, ?string $text): string
    {
        $text = trim((string) $text);

        if ($this->isAutoReply($subject, $text)) {
            return 'auto_reply';
        }

        if ($text === '') {
            return 'other';
        }

        if ($this->ai->isConfigured()) {
            try {
                return $this->ai->classifyReply($text, $subject);
            } catch (AiException $e) {
                report($e);
            }
        }

        return $this->byKeywords($text);
    }

    /**
     * Balasan otomatis yang pasti: subjek out-of-office, atau sapaan/menu otomatis WhatsApp Business.
     */
    public function isAutoReply(?string $subject, ?string $text): bool
    {
        $subject = (string) $subject;
        $text = (string) $text;

        if (preg_match('/^\s*(automatic reply|auto(matic)?[\s-]?reply|autoreply|out of (the )?office|balasan otomatis|auto\s*:)/iu', $subject)) {
            return true;
        }

        if (preg_match('/\b(out of (the )?office|automatic reply|auto[\s-]?reply|balasan otomatis|pesan otomatis|this is an automated (message|response)|sedang cuti|sedang tidak berada di kantor)\b/iu', $text)) {
            return true;
        }

        // Menu bot: "Ketik 1 untuk ..." / "Balas dengan angka ..."
        if (preg_match('/\b(ketik|balas|pilih)\s+(angka\s+)?\d+\s+untuk\b|\bbalas dengan angka\b|\bsilakan pilih menu\b/iu', $text)) {
            return true;
        }

        $thanks = preg_match('/\bterima kasih (telah|sudah|atas) (menghubungi|pesan)|\bthank(s| you) for (contacting|reaching out|your (message|email))/iu', $text);
        $promise = preg_match('/\b(akan (segera )?(membalas|merespon|merespons|dibalas|menghubungi)|jam (operasional|kerja|layanan)|di luar jam|will (get back|reply|respond)|(business|office) hours)\b/iu', $text);

        return $thanks && $promise;
    }

    public function byKeywords(string $text): string
    {
        return match (true) {
            (bool) preg_match('/\b(tidak|tdk|gak|ga|nggak|enggak|belum) (tertarik|perlu|butuh|minat)\b|\bno,? thanks?\b|\bnot interested\b|\bsudah (punya|ada) (website|web|vendor)\b/iu', $text) => 'not_interested',
            (bool) preg_match('/\b(harga|biaya|berapa|tarif|budget|pricelist|price ?list|paket|price|pricing|cost)\b/iu', $text) => 'pricing',
            (bool) preg_match('/\b(tertarik|minat|boleh|mau|silakan|silahkan|kapan|jadwal|diskusi|portofolio|contoh|interested|call|telp|telepon|meeting)\b/iu', $text) => 'interested',
            default => 'other',
        };
    }

    /**
     * Simpan kategori dan terapkan akibatnya ke status pesan & stage lead.
     */
    public function apply(OutreachMessage $message, string $category): void
    {
        $message->loadMissing('lead');
        $lead = $message->lead;

        if ($category === 'auto_reply') {
            // Bukan balasan sungguhan: kembalikan ke "sent" agar sequence tetap berjalan.
            $message->update(['status' => 'sent', 'replied_at' => null, 'reply_category' => 'auto_reply']);

            if ($lead && $lead->pipeline_stage === 'replied' && ! $lead->outreachMessages()->where('status', 'replied')->exists()) {
                $lead->update(['pipeline_stage' => 'contacted']);
            }

            return;
        }

        $message->update(['reply_category' => $category]);

        if (! $lead) {
            return;
        }

        OutreachMessage::cancelOpenFor($lead->id, 'Sequence dihentikan: lead sudah membalas.', sequenceOnly: true);

        if ($category === 'not_interested' && ! in_array($lead->pipeline_stage, ['deal', 'lost'], true)) {
            $lead->update(['pipeline_stage' => 'lost']);
        }

        if (in_array($category, ['interested', 'pricing'], true)) {
            $label = $category === 'pricing' ? 'menanyakan harga' : 'tertarik';
            ScrapingNotification::notify(
                'success',
                'Lead '.($category === 'pricing' ? 'Tanya Harga' : 'Tertarik'),
                "{$lead->displayName()} {$label}".($message->reply_excerpt ? ': "'.mb_strimwidth(trim($message->reply_excerpt), 0, 80, '…').'"' : '.').' Segera tindak lanjuti.'
            );
        }
    }
}
