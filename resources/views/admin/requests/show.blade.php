@extends('layouts.admin')
@section('title', 'Tinjau '.$accessRequest->request_number)
@section('page_title', 'Tinjau permintaan')
@section('content')
<nav class="backline"><a href="{{ route('admin.requests.index') }}">← Permintaan akses</a></nav>
<div class="request-hero"><div><span class="eyebrow">{{ $accessRequest->request_number }}</span><h1>{{ $accessRequest->dataset->title }}</h1><p>Diajukan {{ $accessRequest->created_at->format('d M Y H:i') }}</p></div><span class="status status--large status--{{ $accessRequest->status }}">{{ ucfirst($accessRequest->status) }}</span></div>
<div class="review-grid">
    <div class="review-main">
        <section class="panel"><div class="panel__head"><div><span class="panel-kicker">Applicant</span><h2>Identitas pemohon</h2></div></div><dl class="definition-list"><div><dt>Nama</dt><dd>{{ $accessRequest->user->name }}</dd></div><div><dt>Email</dt><dd>{{ $accessRequest->user->email }}</dd></div><div><dt>Institusi</dt><dd>{{ $accessRequest->user->institution ?: 'Tidak dicantumkan' }}</dd></div><div><dt>Status akun</dt><dd><span class="status status--{{ $accessRequest->user->status }}">{{ ucfirst($accessRequest->user->status) }}</span></dd></div></dl></section>
        <section class="panel"><div class="panel__head"><div><span class="panel-kicker">Research context</span><h2>Tujuan penggunaan</h2></div></div><div class="prose">{!! nl2br(e($accessRequest->research_purpose)) !!}</div></section>
        <section class="panel"><div class="panel__head"><div><span class="panel-kicker">Requested scope</span><h2>Cakupan yang diminta</h2></div></div><dl class="definition-list"><div><dt>Variabel</dt><dd>@foreach($accessRequest->dataset->variables->whereIn('id', $accessRequest->variable_ids) as $variable)<span class="tag">{{ $variable->code }} · {{ $variable->name }}</span>@endforeach</dd></div><div><dt>{{ $accessRequest->dataset->entityLabel() }}</dt><dd>{{ $accessRequest->geographies ? implode(', ', $accessRequest->geographies) : 'Seluruh '.$accessRequest->dataset->entityLabelLower().' tersedia' }}</dd></div><div><dt>Periode</dt><dd>{{ $accessRequest->periods ? implode(', ', $accessRequest->periods) : 'Seluruh periode tersedia' }}</dd></div><div><dt>Estimasi</dt><dd>{{ number_format($accessRequest->estimated_cells) }} sel @if((float)$accessRequest->estimated_price > 0) · Rp{{ number_format((float)$accessRequest->estimated_price, 0, ',', '.') }} @endif</dd></div></dl></section>
    </div>
    <aside class="panel review-decision">
        <span class="panel-kicker">Decision</span><h2>Keputusan akses</h2>
        <form class="form-stack" method="POST" action="{{ route('admin.requests.update', $accessRequest) }}">@csrf @method('PATCH')
            <div class="field"><label for="status">Status keputusan</label><select id="status" name="status" required><option value="approved" @selected(old('status', $accessRequest->status) === 'approved')>Setujui</option><option value="rejected" @selected(old('status', $accessRequest->status) === 'rejected')>Tolak</option></select></div>
            <div class="field"><label for="expires_days">Masa akses <span>hari</span></label><input id="expires_days" name="expires_days" type="number" min="1" max="365" value="{{ old('expires_days', 30) }}"><small>Dipakai hanya jika akses disetujui.</small></div>
            <div class="field"><label for="admin_note">Catatan untuk pemohon</label><textarea id="admin_note" name="admin_note" rows="6" placeholder="Syarat penggunaan, alasan penolakan, atau catatan lisensi.">{{ old('admin_note', $accessRequest->admin_note) }}</textarea></div>
            <button class="button button--ink button--block" type="submit">Simpan keputusan</button>
        </form>
        @if($accessRequest->reviewer)<div class="decision-history"><strong>Terakhir ditinjau</strong><span>{{ $accessRequest->reviewer->name }}</span><small>{{ optional($accessRequest->reviewed_at)->format('d M Y H:i') }}</small></div>@endif
    </aside>
</div>
@endsection
