{{--
    Petunjuk singkat menyalakan scheduler / queue worker yang mati.
    Lokal: `composer run dev`. Server: tiap proses sendiri (cron / Supervisor), lihat SystemHealth::runHint().
--}}
@props(['components' => []])
@if(app()->isLocal())
    Jalankan <code>composer run dev</code>.
@elseif($components)
    Cek di server:
    @foreach($components as $component)
        @php $hint = \App\Services\SystemHealth::runHint($component); @endphp
        {{ $hint['via'] }} <code>{{ $hint['short'] }}</code>{{ $loop->last ? '.' : ',' }}
    @endforeach
@endif
