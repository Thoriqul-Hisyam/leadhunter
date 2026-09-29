{{--
    Layout email bergaya website perusahaan: brand bar (logo + nama), kartu putih, footer gelap.
    Semua style inline + tabel supaya aman di Gmail/Outlook. Section: preheader, content, footer_note.
    Variabel: $brand (App\Mail\EmailBrand::fromSettings()), $showContacts (default true).
    Orb kaca 3D seperti di website adalah PNG transparan yang disematkan (CID): klien email membuang
    blur/backdrop-filter/animasi, dan gambar CID tetap tampil walau APP_URL belum publik.
--}}
@php
    $c = $brand['colors'];
    $font = $brand['font'];
    $showContacts = $showContacts ?? true;
    $footerLink = $brand['whatsapp_url'] ?: $brand['website'];
    // Embed sekali per gambar: setiap embed() menambah satu lampiran inline.
    $orbLime = $message->embed(public_path(\App\Mail\EmailBrand::ORBS['lime']));
    $orbPurple = $message->embed(public_path(\App\Mail\EmailBrand::ORBS['purple']));
@endphp
<!DOCTYPE html>
<html lang="id" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>{{ $brand['name'] }}</title>
    <link href="{{ \App\Mail\EmailBrand::FONT_URL }}" rel="stylesheet">
    <!--[if mso]><style>body,table,td,a,p,span,h1,h2{font-family:Arial,Helvetica,sans-serif !important;}</style><![endif]-->
    <style>
        body { margin: 0; padding: 0; -webkit-text-size-adjust: 100%; }
        a { text-decoration: none; }
        @media (max-width: 620px) {
            .lh-pad { padding: 28px 22px !important; }
            .lh-stack { display: block !important; width: 100% !important; padding: 0 0 10px 0 !important; }
            .lh-h1 { font-size: 26px !important; }
            .lh-num { font-size: 24px !important; }
            .lh-pill { letter-spacing: 0.4px !important; padding: 6px 11px !important; }
            .lh-orb { width: 16% !important; }
        }
    </style>
</head>
<body style="margin:0;padding:0;background-color:{{ $c['page'] }};">
    @hasSection('preheader')
        <div style="display:none;max-height:0;max-width:0;overflow:hidden;opacity:0;mso-hide:all;font-size:1px;line-height:1px;color:{{ $c['page'] }};">@yield('preheader'){!! str_repeat('&#847;&zwnj;&nbsp;', 60) !!}</div>
    @endif

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="{{ $c['page'] }}" style="background-color:{{ $c['page'] }};">
        <tr>
            <td align="center" style="padding:32px 12px 40px;">
                <!--[if mso]><table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0"><tr><td><![endif]-->
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;font-family:{{ $font }};">

                    {{-- Header ala hero website: orb kaca lime & ungu mengapit logo, nama, dan tagline --}}
                    <tr>
                        <td style="padding:0 0 14px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td class="lh-orb" width="22%" align="left" style="width:22%;vertical-align:middle;">
                                        <img src="{{ $orbLime }}" alt="" width="120" style="display:block;width:100%;max-width:120px;height:auto;border:0;">
                                    </td>
                                    <td align="center" style="vertical-align:middle;padding:0 4px;">
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td style="padding-right:10px;vertical-align:middle;">
                                                    @if($brand['logo'])
                                                        <img src="{{ $brand['logo'] }}" alt="" height="36" style="display:block;height:36px;width:auto;max-width:120px;border:0;">
                                                    @else
                                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                                            <tr>
                                                                <td width="34" height="34" align="center" bgcolor="{{ $c['purple'] }}" style="width:34px;height:34px;background-color:{{ $c['purple'] }};border:3px solid {{ $c['lime'] }};border-radius:50%;color:#ffffff;font-family:{{ $font }};font-size:16px;font-weight:800;line-height:34px;">{{ $brand['initial'] }}</td>
                                                            </tr>
                                                        </table>
                                                    @endif
                                                </td>
                                                <td style="vertical-align:middle;font-family:{{ $font }};font-size:24px;font-weight:900;letter-spacing:-0.6px;color:{{ $c['ink'] }};">{{ $brand['name'] }}</td>
                                            </tr>
                                        </table>
                                        @if($brand['tagline'])
                                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin-top:12px;">
                                                <tr>
                                                    <td class="lh-pill" bgcolor="{{ $c['lime'] }}" align="center" style="background-color:{{ $c['lime'] }};border-radius:16px;padding:7px 14px;font-family:{{ $font }};font-size:11px;font-weight:800;letter-spacing:1px;line-height:1.4;text-transform:uppercase;color:{{ $c['ink'] }};">{{ $brand['tagline'] }}</td>
                                                </tr>
                                            </table>
                                        @endif
                                    </td>
                                    <td class="lh-orb" width="22%" align="right" style="width:22%;vertical-align:middle;">
                                        <img src="{{ $orbPurple }}" alt="" width="120" style="display:block;width:100%;max-width:120px;height:auto;border:0;">
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Kartu isi --}}
                    <tr>
                        <td class="lh-pad" bgcolor="{{ $c['card'] }}" style="background-color:{{ $c['card'] }};border:1px solid {{ $c['line'] }};border-radius:28px;padding:40px 44px;">
                            @yield('content')
                        </td>
                    </tr>

                    {{-- Footer gelap, seperti footer website --}}
                    <tr>
                        <td style="padding-top:14px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="{{ $c['ink'] }}" style="background-color:{{ $c['ink'] }};border-radius:28px;">
                                <tr>
                                    <td class="lh-pad" style="padding:32px 44px;font-family:{{ $font }};">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td style="vertical-align:middle;">
                                                    <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                                        <tr>
                                                            <td width="44" height="44" align="center" bgcolor="{{ $c['lime'] }}" style="width:44px;height:44px;background-color:{{ $c['lime'] }};border-radius:14px;vertical-align:middle;font-family:{{ $font }};font-size:18px;font-weight:900;color:{{ $c['ink'] }};">
                                                                @if($brand['logo'])
                                                                    <img src="{{ $brand['logo'] }}" alt="" height="26" style="display:block;margin:0 auto;height:26px;width:auto;max-width:34px;border:0;">
                                                                @else
                                                                    {{ $brand['initial'] }}
                                                                @endif
                                                            </td>
                                                            <td style="padding-left:12px;vertical-align:middle;">
                                                                <div style="font-family:{{ $font }};font-size:20px;font-weight:900;letter-spacing:-0.4px;color:{{ $c['on_ink'] }};line-height:1.2;">{{ $brand['name'] }}</div>
                                                                @if($brand['tagline'])
                                                                    <div style="font-family:{{ $font }};font-size:13px;color:{{ $c['on_ink_muted'] }};line-height:1.5;padding-top:2px;">{{ $brand['tagline'] }}</div>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                                <td width="84" align="right" style="width:84px;vertical-align:middle;">
                                                    @if($showContacts && $footerLink)
                                                        <a href="{{ $footerLink }}" target="_blank" style="text-decoration:none;"><img src="{{ $orbLime }}" alt="" width="84" style="display:block;width:84px;height:auto;border:0;"></a>
                                                    @else
                                                        <img src="{{ $orbLime }}" alt="" width="84" style="display:block;width:84px;height:auto;border:0;">
                                                    @endif
                                                </td>
                                            </tr>
                                        </table>

                                        @if($showContacts && ($brand['phone'] || $brand['website']))
                                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin-top:22px;">
                                                @if($brand['phone'])
                                                    <tr>
                                                        <td style="padding:4px 18px 4px 0;font-family:{{ $font }};font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:{{ $c['on_ink_subtle'] }};">Telepon</td>
                                                        <td style="padding:4px 0;font-family:{{ $font }};font-size:14px;font-weight:600;"><a href="{{ $brand['phone_href'] }}" style="color:{{ $c['on_ink'] }};text-decoration:none;">{{ $brand['phone'] }}</a></td>
                                                    </tr>
                                                @endif
                                                @if($brand['website'])
                                                    <tr>
                                                        <td style="padding:4px 18px 4px 0;font-family:{{ $font }};font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:{{ $c['on_ink_subtle'] }};">Website</td>
                                                        <td style="padding:4px 0;font-family:{{ $font }};font-size:14px;font-weight:600;"><a href="{{ $brand['website'] }}" target="_blank" style="color:{{ $c['lime'] }};text-decoration:none;">{{ $brand['website_label'] }}</a></td>
                                                    </tr>
                                                @endif
                                            </table>
                                        @endif

                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:24px 0 18px;">
                                            <tr><td height="1" style="height:1px;line-height:1px;font-size:1px;background-color:{{ $c['ink_line'] }};">&nbsp;</td></tr>
                                        </table>

                                        <p style="margin:0;font-family:{{ $font }};font-size:12px;line-height:1.6;color:{{ $c['on_ink_subtle'] }};">&copy; {{ now()->year }} <strong style="color:{{ $c['on_ink_muted'] }};">{{ $brand['name'] }}</strong>. Hak cipta dilindungi.</p>
                                        @hasSection('footer_note')
                                            <p style="margin:6px 0 0;font-family:{{ $font }};font-size:12px;line-height:1.6;color:{{ $c['on_ink_subtle'] }};">@yield('footer_note')</p>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
                <!--[if mso]></td></tr></table><![endif]-->
            </td>
        </tr>
    </table>
</body>
</html>
