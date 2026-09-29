@extends('emails.layouts.brand', ['showContacts' => false])

@php
    $r = $report;
    $c = $brand['colors'];
    $font = $brand['font'];
    $h2 = "margin:30px 0 8px;font-family:{$font};font-size:12px;font-weight:800;letter-spacing:1.2px;text-transform:uppercase;color:{$c['purple']};";
    $row = "padding:9px 0;border-bottom:1px solid {$c['line']};font-family:{$font};font-size:14px;color:{$c['text']};";
    $num = "text-align:right;font-weight:700;color:{$c['ink']};white-space:nowrap;padding-left:12px;";
    $kpis = [
        ['Email terkirim', $r['outreach']['email'], null],
        ['WhatsApp terkirim', $r['outreach']['whatsapp'], null],
        ['Balasan', $r['outreach']['replied'], $r['outreach']['reply_rate'].'% · tanpa auto-reply'],
    ];
@endphp

@section('preheader', "{$r['outreach']['email']} email & {$r['outreach']['whatsapp']} WhatsApp terkirim, {$r['outreach']['replied']} balasan dalam 7 hari terakhir.")

@section('content')
    <h1 class="lh-h1" style="margin:0 0 8px;font-family:{{ $font }};font-size:32px;font-weight:800;letter-spacing:-0.8px;line-height:1.2;color:{{ $c['ink'] }};">Laporan <span style="background-color:{{ $c['lime'] }};border-radius:12px;padding:0 10px;">mingguan</span></h1>
    <p style="margin:0 0 26px;font-family:{{ $font }};font-size:14px;color:{{ $c['muted'] }};">{{ $r['from']->translatedFormat('l, d M') }} – {{ $r['until']->translatedFormat('l, d M Y') }}</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            @foreach($kpis as $i => [$label, $value, $hint])
                <td width="33%" style="vertical-align:top;{{ $i < 2 ? 'padding-right:8px;' : '' }}">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="{{ $c['soft'] }}" style="background-color:{{ $c['soft'] }};border:1px solid {{ $c['line'] }};border-radius:18px;">
                        <tr>
                            <td style="padding:16px 14px;font-family:{{ $font }};">
                                <div class="lh-num" style="font-size:30px;font-weight:800;letter-spacing:-0.5px;line-height:1.1;color:{{ $c['purple'] }};">{{ $value }}</div>
                                <div style="padding-top:4px;font-size:12px;font-weight:600;color:{{ $c['ink'] }};">{{ $label }}</div>
                                @if($hint)
                                    <div style="padding-top:2px;font-size:11px;color:{{ $c['muted'] }};">{{ $hint }}</div>
                                @endif
                            </td>
                        </tr>
                    </table>
                </td>
            @endforeach
        </tr>
    </table>

    <h2 style="{{ $h2 }}">Rincian balasan</h2>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
        @foreach(\App\Models\OutreachMessage::REPLY_CATEGORIES as $key => $label)
            @if(($r['categories'][$key] ?? 0) && $key !== 'auto_reply')
                <tr><td style="{{ $row }}">{{ $label }}</td><td style="{{ $row }}{{ $num }}">{{ $r['categories'][$key] }}</td></tr>
            @endif
        @endforeach
        <tr><td style="{{ $row }}">Bounce / minta berhenti</td><td style="{{ $row }}{{ $num }}">{{ $r['outreach']['bounced'] }} / {{ $r['outreach']['unsubscribed'] }}</td></tr>
    </table>

    <h2 style="{{ $h2 }}">Lead</h2>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr><td style="{{ $row }}">Lead baru</td><td style="{{ $row }}{{ $num }}">{{ $r['leads']['new'] }}</td></tr>
        <tr><td style="{{ $row }}">Lead baru berstatus Hot (skor ≥ {{ \App\Models\Lead::HOT_SCORE }})</td><td style="{{ $row }}{{ $num }}">{{ $r['leads']['hot'] }}</td></tr>
        <tr><td style="{{ $row }}">Total lead</td><td style="{{ $row }}{{ $num }}">{{ $r['leads']['total'] }}</td></tr>
    </table>

    @if($r['campaigns']->isNotEmpty())
        <h2 style="{{ $h2 }}">Campaign teratas</h2>
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
            @foreach($r['campaigns'] as $campaign)
                <tr><td style="{{ $row }}">{{ $campaign->name }}</td><td style="{{ $row }}{{ $num }}">{{ $campaign->replied_count }} balasan / {{ $campaign->delivered_count }} terkirim</td></tr>
            @endforeach
        </table>
    @endif

    <h2 style="{{ $h2 }}">Perlu tindakan</h2>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr><td style="{{ $row }}">Draft menunggu review / dikirim</td><td style="{{ $row }}{{ $num }}">{{ $r['todo']['pending'] }}{{ $r['todo']['needs_review'] ? ' ('.$r['todo']['needs_review'].' ditandai perlu review)' : '' }}</td></tr>
        <tr><td style="{{ $row }}">Lead Hot yang belum dihubungi</td><td style="{{ $row }}{{ $num }}">{{ $r['todo']['hot_uncontacted'] }}</td></tr>
        <tr><td style="{{ $row }}">Job gagal di antrean</td><td style="{{ $row }}{{ $num }}{{ $r['todo']['failed_jobs'] ? 'color:'.$c['danger'].';' : '' }}">{{ $r['todo']['failed_jobs'] }}</td></tr>
    </table>

    <h2 style="{{ $h2 }}">AI</h2>
    <p style="margin:0;font-family:{{ $font }};font-size:14px;line-height:1.6;color:{{ $c['text'] }};">{{ $r['ai']['calls'] }} panggilan, {{ $r['ai']['failed'] }} gagal, rata-rata {{ $r['ai']['avg_seconds'] }} detik per panggilan.</p>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin-top:30px;">
        <tr>
            <td bgcolor="{{ $c['ink'] }}" style="background-color:{{ $c['ink'] }};border-radius:999px;">
                <a href="{{ route('dashboard') }}" target="_blank" style="display:inline-block;padding:14px 26px;font-family:{{ $font }};font-size:15px;font-weight:700;color:#ffffff;text-decoration:none;border-radius:999px;">Buka dashboard&nbsp;&rarr;</a>
            </td>
        </tr>
    </table>
@endsection

@section('footer_note', 'Laporan ini dikirim otomatis setiap Senin pagi. Matikan di Pengaturan → Laporan mingguan.')
