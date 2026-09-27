<?php

namespace App\Mail;

use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class OutreachMail extends Mailable
{
    use Queueable, SerializesModels;

    public $messageText;
    public $subjectText;
    public $unsubscribeUrl;
    public $messageIdentifier;
    public $unsubscribeMailto;

    /**
     * @param  string|null  $messageId  Message-ID tanpa tanda kurung siku, disimpan untuk mencocokkan balasan
     * @param  string|null  $unsubscribeMailto  dipakai jika app tidak punya URL publik untuk link unsubscribe
     */
    public function __construct(string $subjectText, string $messageText, ?string $unsubscribeUrl = null, ?string $messageId = null, ?string $unsubscribeMailto = null)
    {
        $this->subjectText = $subjectText;
        $this->messageText = $messageText;
        $this->unsubscribeUrl = $unsubscribeUrl;
        $this->messageIdentifier = $messageId;
        $this->unsubscribeMailto = $unsubscribeMailto;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectText,
        );
    }

    public function headers(): Headers
    {
        $text = [];

        if ($this->unsubscribeUrl) {
            $text['List-Unsubscribe'] = '<'.$this->unsubscribeUrl.'>'.($this->unsubscribeMailto ? ', <'.$this->unsubscribeMailto.'>' : '');
            $text['List-Unsubscribe-Post'] = 'List-Unsubscribe=One-Click';
        } elseif ($this->unsubscribeMailto) {
            $text['List-Unsubscribe'] = '<'.$this->unsubscribeMailto.'>';
        }

        return new Headers(
            messageId: $this->messageIdentifier,
            text: $text,
        );
    }

    public function content(): Content
    {
        $settings = Setting::values();

        return new Content(
            view: 'emails.outreach',
            with: [
                'messageText' => $this->messageText,
                'unsubscribeUrl' => $this->unsubscribeUrl,
                'companyName' => $settings['company_name'],
                'companyTagline' => $settings['company_tagline'],
                'companyPhone' => $settings['company_phone'],
                'companyWebsite' => $settings['company_website'],
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
