@extends('layouts.admin')

@section('title', 'Koleksi data')
@section('page_title', 'Koleksi data')

@section('content')
<div class="admin-library">
    <header class="admin-library__header" data-motion-item>
        <div>
            <span class="admin-library__eyebrow">Katalog / Pengelolaan</span>
            <h1>Koleksi data</h1>
            <p>Pilih satu koleksi. Variabel, pengisian, pemeriksaan, dan publikasinya dikerjakan dari halaman koleksi tersebut.</p>
        </div>
        <a class="admin-library__create" href="{{ route('admin.datasets.create') }}">+ Buat koleksi</a>
    </header>

    <form class="admin-library__filters" method="GET" action="{{ route('admin.datasets.index') }}" aria-label="Filter koleksi data">
        <div class="admin-library__field admin-library__field--search">
            <label for="collection-search">Cari koleksi</label>
            <input id="collection-search" name="q" type="search" value="{{ request('q') }}" placeholder="Judul atau kode">
        </div>
        <div class="admin-library__field">
            <label for="collection-scope">Jenis data</label>
            <select id="collection-scope" name="scope">
                <option value="">Semua jenis</option>
                <option value="regional" @selected(request('scope') === 'regional')>BPS &amp; pemerintah</option>
                <option value="corporate" @selected(request('scope') === 'corporate')>Perusahaan &amp; ESG</option>
            </select>
        </div>
        <div class="admin-library__field">
            <label for="collection-status">Status</label>
            <select id="collection-status" name="status">
                <option value="">Semua status</option>
                <option value="draft" @selected(request('status') === 'draft')>Draft</option>
                <option value="published" @selected(request('status') === 'published')>Terbit</option>
                <option value="archived" @selected(request('status') === 'archived')>Arsip</option>
            </select>
        </div>
        <button type="submit">Terapkan</button>
        @if(request()->hasAny(['q', 'scope', 'status']))
            <a class="admin-library__reset" href="{{ route('admin.datasets.index') }}">Hapus filter</a>
        @endif
    </form>

    <section class="admin-library__list" aria-labelledby="collection-list-title">
        <div class="admin-library__list-head">
            <div><span class="admin-library__eyebrow">Daftar kerja</span><h2 id="collection-list-title">{{ number_format($datasets->total()) }} koleksi ditemukan</h2></div>
            <a href="{{ route('admin.providers.index') }}">Kelola sumber data <span aria-hidden="true">↗</span></a>
        </div>

        @forelse($datasets as $dataset)
            @php
                $next = $dataset->provider->status !== 'active'
                    ? 'Aktifkan sumber data'
                    : ($dataset->active_variables_count < 1
                        ? 'Tambahkan variabel'
                        : ($dataset->active_observations_count < 1
                            ? 'Isi angka'
                            : ($dataset->pending_quality_count > 0
                                ? 'Periksa angka'
                                : ($dataset->status === 'published' ? 'Sudah tayang' : 'Periksa kesiapan terbit'))));
                $statusLabel = ['draft' => 'Draft', 'published' => 'Terbit', 'archived' => 'Arsip'][$dataset->status] ?? ucfirst($dataset->status);
            @endphp
            <article class="admin-library__row">
                <div class="admin-library__identity">
                    <div class="admin-library__tags">
                        <span class="admin-library__code">{{ $dataset->code }}</span>
                        <span class="admin-library__scope">{{ $dataset->isCorporate() ? 'Perusahaan & ESG' : 'BPS & pemerintah' }}</span>
                    </div>
                    <h3><a href="{{ route('admin.datasets.edit', $dataset) }}">{{ $dataset->title }}</a></h3>
                    <p>{{ $dataset->provider->name }} · {{ ['open' => 'Terbuka', 'restricted' => 'Terbatas', 'commercial' => 'Berlisensi'][$dataset->access_type] ?? ucfirst($dataset->access_type) }}</p>
                </div>
                <div class="admin-library__counts" aria-label="Isi koleksi">
                    <span><strong>{{ number_format($dataset->variables_count) }}</strong> variabel</span>
                    <span><strong>{{ number_format($dataset->observations_count) }}</strong> angka</span>
                </div>
                <div class="admin-library__progress">
                    <span class="admin-library__status admin-library__status--{{ $dataset->status }}">{{ $statusLabel }}</span>
                    <small>Berikutnya: {{ $next }}</small>
                </div>
                <a class="admin-library__open" href="{{ route('admin.datasets.edit', $dataset) }}" aria-label="Kelola {{ $dataset->title }}">Kelola <span aria-hidden="true">→</span></a>
            </article>
        @empty
            <div class="admin-library__empty">
                <strong>Tidak ada koleksi pada daftar ini.</strong>
                <p>{{ request()->hasAny(['q', 'scope', 'status']) ? 'Coba hapus filter atau gunakan kata kunci lain.' : 'Daftarkan sumber data, lalu buat koleksi pertama.' }}</p>
                <a href="{{ request()->hasAny(['q', 'scope', 'status']) ? route('admin.datasets.index') : route('admin.datasets.create') }}">{{ request()->hasAny(['q', 'scope', 'status']) ? 'Lihat semua koleksi' : 'Buat koleksi' }} →</a>
            </div>
        @endforelse

        @if($datasets->hasPages())
            <div class="admin-library__pagination">{{ $datasets->links() }}</div>
        @endif
    </section>
</div>
@endsection
