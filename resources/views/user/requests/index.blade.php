@extends('layouts.public')

@section('title', 'Permintaan akses')

@section('content')
<section class="access-page">
    <div class="container-wide">
        <a class="access-page__back" href="{{ route('user.profile') }}">← Kembali ke profil</a>

        <div class="access-page__intro" data-motion-item>
            <div>
                <span class="eyebrow">Ruang peneliti</span>
                <h1>Permintaan akses</h1>
                <p>Pantau data yang kamu ajukan, keputusan pengelola, dan masa berlaku akses dari satu tempat.</p>
            </div>
            <a class="button button--ink" href="{{ route('datasets.index') }}">Jelajahi katalog</a>
        </div>

        <div class="access-summary" data-motion-item>
            <a class="access-summary__item {{ $status === '' ? 'is-active' : '' }}" href="{{ route('user.requests.index') }}">
                <span>Semua permintaan</span>
                <strong>{{ number_format((int) ($requestCounts['all'] ?? 0)) }}</strong>
                <small>Seluruh riwayat</small>
            </a>
            <a class="access-summary__item {{ $status === 'pending' ? 'is-active' : '' }}" href="{{ route('user.requests.index', ['status' => 'pending']) }}">
                <span>Menunggu</span>
                <strong>{{ number_format((int) ($requestCounts['pending'] ?? 0)) }}</strong>
                <small>Perlu keputusan</small>
            </a>
            <a class="access-summary__item {{ $status === 'approved' ? 'is-active' : '' }}" href="{{ route('user.requests.index', ['status' => 'approved']) }}">
                <span>Disetujui</span>
                <strong>{{ number_format((int) ($requestCounts['approved'] ?? 0)) }}</strong>
                <small>Akses tersedia</small>
            </a>
            <a class="access-summary__item {{ $status === 'rejected' ? 'is-active' : '' }}" href="{{ route('user.requests.index', ['status' => 'rejected']) }}">
                <span>Ditolak</span>
                <strong>{{ number_format((int) ($requestCounts['rejected'] ?? 0)) }}</strong>
                <small>Perlu ditinjau ulang</small>
            </a>
        </div>

        <div class="access-list-heading" data-motion-item>
            <div>
                <span class="eyebrow">Riwayat data</span>
                <h2>{{ $status === '' ? 'Semua permintaan' : ucfirst($status) }}</h2>
            </div>
            <span class="access-result-count">{{ number_format($requests->total()) }} hasil</span>
        </div>

        <nav class="access-filter-tabs" aria-label="Filter permintaan akses">
            <a class="{{ $status === '' ? 'is-active' : '' }}" href="{{ route('user.requests.index') }}">Semua</a>
            <a class="{{ $status === 'pending' ? 'is-active' : '' }}" href="{{ route('user.requests.index', ['status' => 'pending']) }}">Menunggu</a>
            <a class="{{ $status === 'approved' ? 'is-active' : '' }}" href="{{ route('user.requests.index', ['status' => 'approved']) }}">Disetujui</a>
            <a class="{{ $status === 'rejected' ? 'is-active' : '' }}" href="{{ route('user.requests.index', ['status' => 'rejected']) }}">Ditolak</a>
        </nav>

        @if($requests->isEmpty())
            <section class="access-empty" data-motion-item>
                <span class="access-empty__mark" aria-hidden="true">+</span>
                <span class="eyebrow">Belum ada permintaan</span>
                <h2>Mulai dari variabel yang kamu perlukan.</h2>
                <p>Data terbuka dapat langsung dilihat. Untuk data terbatas atau berlisensi, kirimkan tujuan penggunaan agar pengelola dapat meninjau permintaanmu.</p>
                <a class="button button--ink" href="{{ route('datasets.index') }}">Buka katalog variabel</a>
            </section>
        @else
            <div class="access-request-list">
                @foreach($requests as $item)
                    <article class="access-request-card" data-motion-item>
                        <div class="access-request-card__top">
                            <div>
                                <span class="access-request-number">{{ $item->request_number }}</span>
                                <h3>{{ $item->dataset->title }}</h3>
                                <p>{{ $item->dataset->provider->name }}</p>
                            </div>
                            <span class="status status--{{ $item->status }}">{{ ucfirst($item->status) }}</span>
                        </div>
                        <div class="access-request-card__meta">
                            <div><span>Variabel</span><strong>{{ count($item->variable_ids) }}</strong><small>Pilihan indikator</small></div>
                            <div><span>Estimasi keluaran</span><strong>{{ number_format($item->estimated_cells) }}</strong><small>Sel data</small></div>
                            <div><span>Diajukan</span><strong>{{ $item->created_at->format('d M Y') }}</strong><small>{{ $item->created_at->format('H:i') }} WIB</small></div>
                        </div>
                        <div class="access-request-card__bottom">
                            <span>{{ $item->dataset->entityLabel() }} · {{ $item->dataset->scopeLabel() }}</span>
                            <a href="{{ route('user.requests.show', $item) }}">Lihat detail <span aria-hidden="true">→</span></a>
                        </div>
                    </article>
                @endforeach
            </div>
            <div class="access-pagination">{{ $requests->links() }}</div>
        @endif
    </div>
</section>
@endsection
