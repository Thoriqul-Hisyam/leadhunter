{{-- Bagian text/plain email outreach (plain text, jadi tanpa escape HTML) --}}
{!! trim($messageText) !!}

@if($brand['phone'] || $brand['website'])
--
{!! $brand['name'] !!}
@if($brand['phone'])
Telepon/WA: {!! $brand['phone'] !!}
@endif
@if($brand['website'])
Website: {!! $brand['website'] !!}
@endif
@endif

@if($unsubscribeUrl)
Tidak ingin menerima email seperti ini lagi? Berhenti berlangganan: {!! $unsubscribeUrl !!}
@else
Tidak ingin menerima email seperti ini lagi? Balas email ini dengan kata BERHENTI.
@endif
