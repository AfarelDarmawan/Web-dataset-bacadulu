@extends('layouts.public')

@section('title', $accessRequest->request_number)

@section('content')
<section class="access-page access-page--detail">
    <div class="container-wide">
        <a class="access-page__back" href="{{ route('user.requests.index') }}">← Semua permintaan</a>

        <div class="request-detail-heading" data-motion-item>
            <div>
                <span class="eyebrow">{{ $accessRequest->request_number }}</span>
                <h1>{{ $accessRequest->dataset->title }}</h1>
                <p>{{ $accessRequest->dataset->provider->name }}</p>
            </div>
            <span class="status status--large status--{{ $accessRequest->status }}">{{ ucfirst($accessRequest->status) }}</span>
        </div>

        <div class="request-detail-grid">
            <section class="access-detail-card" data-motion-item>
                <div class="access-detail-card__head">
                    <div><span class="eyebrow">Ruang lingkup</span><h2>Data yang diajukan</h2></div>
                    <span class="access-detail-card__index">01</span>
                </div>
                <dl class="access-definition-list">
                    <div><dt>Variabel</dt><dd>@foreach($accessRequest->dataset->variables->whereIn('id', $accessRequest->variable_ids) as $variable)<span class="access-tag">{{ $variable->code }} · {{ $variable->name }}</span>@endforeach</dd></div>
                    <div><dt>{{ $accessRequest->dataset->entityLabel() }}</dt><dd>{{ $accessRequest->geographies ? implode(', ', $accessRequest->geographies) : 'Seluruh '.$accessRequest->dataset->entityLabelLower().' tersedia' }}</dd></div>
                    <div><dt>Periode</dt><dd>{{ $accessRequest->periods ? implode(', ', $accessRequest->periods) : 'Seluruh periode tersedia' }}</dd></div>
                    <div><dt>Estimasi keluaran</dt><dd>{{ number_format($accessRequest->estimated_cells) }} sel data</dd></div>
                    @if((float) $accessRequest->estimated_price > 0)
                        <div><dt>Estimasi biaya</dt><dd>Rp{{ number_format($accessRequest->estimated_price, 0, ',', '.') }}</dd></div>
                    @endif
                </dl>
            </section>

            <aside class="access-detail-card access-detail-card--decision" data-motion-item>
                <div class="access-detail-card__head">
                    <div><span class="eyebrow">Keputusan akses</span><h2>Status permintaan</h2></div>
                    <span class="access-detail-card__index">02</span>
                </div>
                @if($accessRequest->status === 'pending')
                    <div class="decision-mark decision-mark--pending" aria-hidden="true">…</div>
                    <h3>Sedang ditinjau</h3>
                    <p>Admin sedang memeriksa tujuan penggunaan dan cakupan data. Keputusan akan muncul di halaman ini.</p>
                @elseif($accessRequest->status === 'approved')
                    <div class="decision-mark decision-mark--approved" aria-hidden="true">✓</div>
                    <h3>Akses disetujui</h3>
                    <p>Data dapat diunduh sampai {{ optional($accessRequest->expires_at)->format('d M Y H:i') ?: 'batas yang ditentukan pengelola' }}.</p>
                    @if($accessRequest->canDownload() && $accessRequest->dataset->isPubliclyAvailable())
                        <form method="POST" action="{{ route('user.requests.download', $accessRequest) }}">@csrf<button class="button button--ink button--block" type="submit">Unduh CSV</button></form>
                    @else
                        <div class="access-alert">Akses berakhir atau dataset sedang tidak tersedia.</div>
                    @endif
                @else
                    <div class="decision-mark decision-mark--rejected" aria-hidden="true">×</div>
                    <h3>Permintaan ditolak</h3>
                    <p>Periksa catatan pengelola. Kamu dapat mengajukan permintaan baru dengan cakupan atau tujuan yang lebih jelas.</p>
                @endif
                @if($accessRequest->admin_note)
                    <div class="admin-note"><strong>Catatan admin</strong><p>{{ $accessRequest->admin_note }}</p></div>
                @endif
            </aside>
        </div>

        <section class="access-detail-card access-detail-card--purpose" data-motion-item>
            <div class="access-detail-card__head">
                <div><span class="eyebrow">Konteks penelitian</span><h2>Tujuan penggunaan</h2></div>
                <span class="access-detail-card__index">03</span>
            </div>
            <div class="access-purpose">{!! nl2br(e($accessRequest->research_purpose)) !!}</div>
        </section>
    </div>
</section>
@endsection
