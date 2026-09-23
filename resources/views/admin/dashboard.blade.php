@extends('layouts.admin')

@section('title', 'Beranda Admin')
@section('page_title', 'Beranda admin')

@section('content')
@php
    $stagingCount = ($stats['review_syncs'] ?? 0) + ($stats['review_extractions'] ?? 0);
    $qualityCount = ($stats['unreviewed'] ?? 0) + ($stats['flagged'] ?? 0);
    $pendingCount = $stats['pending_requests'] ?? 0;
    $activeProviders = $stats['active_providers'] ?? $stats['providers'] ?? 0;

    if (($stats['flagged'] ?? 0) > 0) {
        $nextTitle = number_format($stats['flagged']).' angka ditandai perlu keputusan';
        $nextText = 'Periksa nilai dan sumbernya sebelum koleksi diterbitkan.';
        $nextUrl = route('admin.quality.index');
        $nextButton = 'Periksa angka';
    } elseif ($stagingCount > 0) {
        $nextTitle = number_format($stagingCount).' hasil pengambilan menunggu pemeriksaan';
        $nextText = 'Tinjau hasil BPS atau dokumen sebelum masuk ke koleksi.';
        $nextUrl = route('admin.quality.index');
        $nextButton = 'Tinjau hasil';
    } elseif ($qualityCount > 0) {
        $nextTitle = number_format($qualityCount).' angka belum selesai ditinjau';
        $nextText = 'Periksa angka baru agar koleksi bisa diterbitkan.';
        $nextUrl = route('admin.quality.index');
        $nextButton = 'Buka pemeriksaan';
    } elseif ($pendingCount > 0) {
        $nextTitle = number_format($pendingCount).' permintaan akses menunggu jawaban';
        $nextText = 'Periksa tujuan penelitian, cakupan, dan periode yang diminta.';
        $nextUrl = route('admin.requests.index', ['status' => 'pending']);
        $nextButton = 'Tinjau permintaan';
    } elseif ($activeProviders < 1) {
        $nextTitle = 'Mulai dari sumber data';
        $nextText = 'Daftarkan atau aktifkan BPS dan penyedia lain sebelum membuat koleksi.';
        $nextUrl = route('admin.providers.index');
        $nextButton = 'Kelola sumber';
    } elseif (($stats['datasets'] ?? 0) < 1) {
        $nextTitle = 'Buat koleksi pertama';
        $nextText = 'Satu koleksi menyimpan definisi variabel, angka, dan aturan akses.';
        $nextUrl = route('admin.datasets.create');
        $nextButton = 'Buat koleksi';
    } elseif (($stats['drafts'] ?? 0) > 0) {
        $nextTitle = 'Lanjutkan koleksi yang belum terbit';
        $nextText = 'Buka koleksi draft dan ikuti tahapannya sampai siap diterbitkan.';
        $nextUrl = route('admin.datasets.index', ['status' => 'draft']);
        $nextButton = 'Lihat koleksi draft';
    } else {
        $nextTitle = 'Semua pekerjaan utama sudah tertangani';
        $nextText = 'Koleksi tetap bisa diperbarui kapan saja dari daftar koleksi.';
        $nextUrl = route('admin.datasets.index');
        $nextButton = 'Lihat koleksi';
    }
@endphp

<div class="admin-work" aria-label="Beranda pengelolaan data">
    <header class="admin-work__heading" data-motion-item>
        <div>
            <span class="admin-work__eyebrow">BacaDulu / Pengelolaan data</span>
            <h1>Selamat datang, {{ auth('admin')->user()->name }}.</h1>
            <p>Mulai dari satu pekerjaan paling penting, lalu lanjutkan koleksi yang sedang dikerjakan.</p>
        </div>
        <a class="admin-work__catalog" href="{{ route('datasets.index') }}" target="_blank" rel="noopener">Lihat katalog publik <span aria-hidden="true">↗</span></a>
    </header>

    <section class="admin-work__priority" aria-labelledby="admin-work-priority" data-motion-item>
        <div>
            <span class="admin-work__priority-label"><span class="admin-work__priority-dot" aria-hidden="true"></span> Langkah berikutnya</span>
            <h2 id="admin-work-priority">{{ $nextTitle }}</h2>
            <p>{{ $nextText }}</p>
        </div>
        <a class="admin-work__primary-link" href="{{ $nextUrl }}">{{ $nextButton }} <span aria-hidden="true">→</span></a>
    </section>

    <section class="admin-work__section" aria-labelledby="admin-work-queues" data-motion-item>
        <div class="admin-work__section-head">
            <div><span class="admin-work__eyebrow">Antrean hari ini</span><h2 id="admin-work-queues">Apa yang perlu perhatian?</h2></div>
            <span class="admin-work__section-note">Angka mengikuti status data saat ini</span>
        </div>
        <div class="admin-work__queues">
            <a href="{{ route('admin.quality.index') }}"><span>Hasil BPS &amp; dokumen</span><strong>{{ number_format($stagingCount) }}</strong><small>Periksa hasil sebelum diterapkan <span aria-hidden="true">→</span></small></a>
            <a href="{{ route('admin.quality.index') }}"><span>Angka perlu pemeriksaan</span><strong>{{ number_format($qualityCount) }}</strong><small>{{ number_format($stats['flagged'] ?? 0) }} ditandai · {{ number_format($stats['unreviewed'] ?? 0) }} belum ditinjau <span aria-hidden="true">→</span></small></a>
            <a href="{{ route('admin.requests.index', ['status' => 'pending']) }}"><span>Permintaan peneliti</span><strong>{{ number_format($pendingCount) }}</strong><small>Menunggu keputusan akses <span aria-hidden="true">→</span></small></a>
        </div>
    </section>

    <div class="admin-work__columns" data-motion-item>
        <section class="admin-work__panel" aria-labelledby="admin-work-collections">
            <div class="admin-work__panel-head">
                <div><span class="admin-work__eyebrow">Lanjutkan pekerjaan</span><h2 id="admin-work-collections">Koleksi terbaru</h2></div>
                <a href="{{ route('admin.datasets.index') }}">Semua koleksi <span aria-hidden="true">→</span></a>
            </div>
            @forelse($recentDatasets as $dataset)
                <a class="admin-work__collection" href="{{ route('admin.datasets.edit', $dataset) }}">
                    <span class="admin-work__collection-main"><strong>{{ $dataset->title }}</strong><small>{{ $dataset->provider->name }} · {{ number_format($dataset->variables_count) }} variabel · {{ number_format($dataset->observations_count) }} angka</small></span>
                    <span class="admin-work__collection-end"><span class="admin-work__state admin-work__state--{{ $dataset->status }}">{{ ['draft' => 'Draft', 'published' => 'Terbit', 'archived' => 'Arsip'][$dataset->status] ?? ucfirst($dataset->status) }}</span><span aria-hidden="true">→</span></span>
                </a>
            @empty
                <div class="admin-work__empty"><strong>Belum ada koleksi.</strong><p>Setelah sumber data aktif, buat koleksi pertama.</p><a href="{{ route('admin.datasets.create') }}">Buat koleksi <span aria-hidden="true">→</span></a></div>
            @endforelse
        </section>

        <aside class="admin-work__aside" aria-labelledby="admin-work-overview">
            <span class="admin-work__eyebrow">Gambaran katalog</span>
            <h2 id="admin-work-overview">Data yang dikelola</h2>
            <dl>
                <div><dt>Koleksi</dt><dd>{{ number_format($stats['datasets'] ?? 0) }}</dd></div>
                <div><dt>Variabel</dt><dd>{{ number_format($stats['variables'] ?? 0) }}</dd></div>
                <div><dt>Angka</dt><dd>{{ number_format($stats['observations'] ?? 0) }}</dd></div>
                <div><dt>Terbit</dt><dd>{{ number_format($stats['published'] ?? 0) }}</dd></div>
            </dl>
            <details><summary>Bagaimana alur admin bekerja?</summary><ol><li>Daftarkan sumber data.</li><li>Buat koleksi dan variabel.</li><li>Isi angka lewat CSV, BPS, atau dokumen.</li><li>Periksa hasil, lalu terbitkan.</li></ol></details>
        </aside>
    </div>
</div>
@endsection
