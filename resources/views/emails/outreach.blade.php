@extends('emails.layouts.brand')

@php
    $c = $brand['colors'];
    $font = $brand['font'];
    $paragraphs = preg_split('/\R[ \t]*\R/u', trim($messageText)) ?: [$messageText];
@endphp

{{-- Teks pratinjau di inbox: pembuka pesan, bukan nama perusahaan di header --}}
@section('preheader', \Illuminate\Support\Str::limit(preg_replace('/\s+/u', ' ', $messageText), 120))

@section('content')
    @foreach($paragraphs as $paragraph)
        <p style="margin:0 0 18px;font-family:{{ $font }};font-size:16px;line-height:1.75;color:{{ $c['text'] }};">{!! nl2br(e(trim($paragraph, "\r\n"))) !!}</p>
    @endforeach

    @if($brand['whatsapp_url'] || $brand['website'])
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin-top:28px;">
            <tr>
                @if($brand['whatsapp_url'])
                    <td class="lh-stack" style="padding-right:10px;">
                        <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td bgcolor="{{ $c['ink'] }}" style="background-color:{{ $c['ink'] }};border-radius:999px;">
                                    <a href="{{ $brand['whatsapp_url'] }}" target="_blank" style="display:inline-block;padding:14px 26px;font-family:{{ $font }};font-size:15px;font-weight:700;color:#ffffff;text-decoration:none;border-radius:999px;">Hubungi via WhatsApp&nbsp;&rarr;</a>
                                </td>
                            </tr>
                        </table>
                    </td>
                @endif
                @if($brand['website'])
                    <td class="lh-stack">
                        <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td style="border:1.5px solid {{ $c['ink'] }};border-radius:999px;">
                                    <a href="{{ $brand['website'] }}" target="_blank" style="display:inline-block;padding:12px 24px;font-family:{{ $font }};font-size:15px;font-weight:600;color:{{ $c['ink'] }};text-decoration:none;border-radius:999px;">{{ $brand['website_label'] }}</a>
                                </td>
                            </tr>
                        </table>
                    </td>
                @endif
            </tr>
        </table>
    @endif
@endsection

@section('footer_note')
    @if($unsubscribeUrl)
        Tidak ingin menerima email seperti ini lagi? <a href="{{ $unsubscribeUrl }}" style="color:{{ $c['lime'] }};text-decoration:underline;">Berhenti berlangganan</a>
    @else
        Tidak ingin menerima email seperti ini lagi? Balas email ini dengan kata <strong style="color:{{ $c['on_ink'] }};">BERHENTI</strong>.
    @endif
@endsection
