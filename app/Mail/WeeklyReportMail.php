<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WeeklyReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public array $report)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Laporan mingguan LeadHunter: '.$this->report['from']->translatedFormat('d M').' – '.$this->report['until']->translatedFormat('d M Y'),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.weekly-report');
    }
}
