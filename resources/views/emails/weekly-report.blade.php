@php
    $r = $report;
    $row = 'padding:6px 0;border-bottom:1px solid #eef0f3;';
    $num = 'text-align:right;font-weight:700;color:#0f172a;';
@endphp
<!DOCTYPE html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="margin:0;padding:24px 12px;background:#f5f6f8;font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;color:#334155;font-size:14px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:12px;padding:24px;">
    <tr><td>
        <h1 style="margin:0 0 4px;font-size:18px;color:#0f172a;">Laporan mingguan</h1>
        <p style="margin:0 0 20px;color:#64748b;font-size:13px;">{{ $r['from']->translatedFormat('l, d M') }} – {{ $r['until']->translatedFormat('l, d M Y') }}</p>

        <h2 style="font-size:13px;text-transform:uppercase;letter-spacing:.05em;color:#64748b;margin:0 0 6px;">Outreach</h2>
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;">
            <tr><td style="{{ $row }}">Email terkirim</td><td style="{{ $row }}{{ $num }}">{{ $r['outreach']['email'] }}</td></tr>
            <tr><td style="{{ $row }}">WhatsApp terkirim</td><td style="{{ $row }}{{ $num }}">{{ $r['outreach']['whatsapp'] }}</td></tr>
            <tr><td style="{{ $row }}">Balasan (tanpa balasan otomatis)</td><td style="{{ $row }}{{ $num }}">{{ $r['outreach']['replied'] }} ({{ $r['outreach']['reply_rate'] }}%)</td></tr>
            @foreach(\App\Models\OutreachMessage::REPLY_CATEGORIES as $key => $label)
                @if(($r['categories'][$key] ?? 0) && $key !== 'auto_reply')
                    <tr><td style="{{ $row }}padding-left:14px;color:#64748b;">{{ $label }}</td><td style="{{ $row }}{{ $num }}">{{ $r['categories'][$key] }}</td></tr>
                @endif
            @endforeach
            <tr><td style="{{ $row }}">Bounce / minta berhenti</td><td style="{{ $row }}{{ $num }}">{{ $r['outreach']['bounced'] }} / {{ $r['outreach']['unsubscribed'] }}</td></tr>
        </table>

        <h2 style="font-size:13px;text-transform:uppercase;letter-spacing:.05em;color:#64748b;margin:0 0 6px;">Lead</h2>
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;">
            <tr><td style="{{ $row }}">Lead baru</td><td style="{{ $row }}{{ $num }}">{{ $r['leads']['new'] }}</td></tr>
            <tr><td style="{{ $row }}">Lead baru berstatus Hot (skor ≥ {{ \App\Models\Lead::HOT_SCORE }})</td><td style="{{ $row }}{{ $num }}">{{ $r['leads']['hot'] }}</td></tr>
            <tr><td style="{{ $row }}">Total lead</td><td style="{{ $row }}{{ $num }}">{{ $r['leads']['total'] }}</td></tr>
        </table>

        @if($r['campaigns']->isNotEmpty())
            <h2 style="font-size:13px;text-transform:uppercase;letter-spacing:.05em;color:#64748b;margin:0 0 6px;">Campaign teratas</h2>
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;">
                @foreach($r['campaigns'] as $c)
                    <tr><td style="{{ $row }}">{{ $c->name }}</td><td style="{{ $row }}{{ $num }}">{{ $c->replied_count }} balasan / {{ $c->delivered_count }} terkirim</td></tr>
                @endforeach
            </table>
        @endif

        <h2 style="font-size:13px;text-transform:uppercase;letter-spacing:.05em;color:#64748b;margin:0 0 6px;">Perlu tindakan</h2>
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;">
            <tr><td style="{{ $row }}">Draft menunggu review / dikirim</td><td style="{{ $row }}{{ $num }}">{{ $r['todo']['pending'] }}{{ $r['todo']['needs_review'] ? ' ('.$r['todo']['needs_review'].' ditandai perlu review)' : '' }}</td></tr>
            <tr><td style="{{ $row }}">Lead Hot yang belum dihubungi</td><td style="{{ $row }}{{ $num }}">{{ $r['todo']['hot_uncontacted'] }}</td></tr>
            <tr><td style="{{ $row }}">Job gagal di antrean</td><td style="{{ $row }}{{ $num }}{{ $r['todo']['failed_jobs'] ? 'color:#e11d48;' : '' }}">{{ $r['todo']['failed_jobs'] }}</td></tr>
        </table>

        <h2 style="font-size:13px;text-transform:uppercase;letter-spacing:.05em;color:#64748b;margin:0 0 6px;">AI</h2>
        <p style="margin:0 0 20px;">{{ $r['ai']['calls'] }} panggilan, {{ $r['ai']['failed'] }} gagal, rata-rata {{ $r['ai']['avg_seconds'] }} detik per panggilan.</p>

        <p style="margin:0;"><a href="{{ route('dashboard') }}" style="display:inline-block;background:#4f46e5;color:#ffffff;text-decoration:none;font-weight:700;padding:10px 16px;border-radius:8px;">Buka dashboard</a></p>
        <p style="margin:16px 0 0;font-size:12px;color:#94a3b8;">Laporan ini dikirim otomatis setiap Senin pagi. Matikan di Pengaturan → Laporan mingguan.</p>
    </td></tr>
</table>
</body>
</html>
