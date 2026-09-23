@extends('layouts.admin')
@section('title', 'Kelola '.$dataset->title)
@section('page_title', 'Kelola koleksi')
@section('content')
@php
    $sourceReady = $dataset->provider->status === 'active';
    $variablesReady = $dataset->active_variables_count > 0;
    $dataReady = $dataset->active_observations_count > 0;
    $qualityReady = $dataReady && $dataset->pending_quality_count === 0;
    $readyToPublish = $sourceReady && $variablesReady && $qualityReady;
    $statusLabel = ['draft' => 'Draft', 'published' => 'Terbit', 'archived' => 'Arsip'][$dataset->status] ?? ucfirst($dataset->status);
@endphp

<header class="dataset-admin-head">
    <div>
        <span class="eyebrow">{{ $dataset->code }} / {{ $dataset->scopeLabel() }}</span>
        <h1>{{ $dataset->title }}</h1>
        <p>Sumber {{ $dataset->provider->name }} · Akses {{ ['open' => 'terbuka', 'restricted' => 'terbatas', 'commercial' => 'berlisensi'][$dataset->access_type] ?? $dataset->access_type }}</p>
    </div>
    <span class="status status--large status--{{ $dataset->status }}">{{ $statusLabel }}</span>
</header>

<section class="admin-next-action" aria-labelledby="admin-next-title">
    <div>
        <span class="admin-next-action__label">Langkah berikutnya</span>
        @if(!$sourceReady)
            <h2 id="admin-next-title">Aktifkan sumber data.</h2>
            <p>Sumber yang belum aktif menghalangi publikasi koleksi ini.</p>
        @elseif(!$variablesReady)
            <h2 id="admin-next-title">Tambahkan variabel.</h2>
            <p>Definisikan indikator dan satuannya sebelum mengisi angka.</p>
        @elseif(!$dataReady)
            <h2 id="admin-next-title">Masukkan data pertama.</h2>
            <p>Gunakan CSV, sinkronisasi BPS, atau dokumen sumber.</p>
        @elseif(!$qualityReady)
            <h2 id="admin-next-title">Periksa {{ number_format($dataset->pending_quality_count) }} angka.</h2>
            <p>Angka yang belum ditinjau atau ditandai harus ditangani sebelum terbit.</p>
        @elseif($dataset->status === 'published')
            <h2 id="admin-next-title">Koleksi sudah terbit.</h2>
            <p>Lihat hasilnya di katalog publik atau kelola permintaan aksesnya.</p>
        @else
            <h2 id="admin-next-title">Siap diterbitkan.</h2>
            <p>Periksa metadata dan jenis akses, lalu terbitkan koleksi ini.</p>
        @endif
    </div>
    @if(!$sourceReady)
        <a class="button button--ink" href="{{ route('admin.providers.edit', $dataset->provider) }}">Atur sumber</a>
    @elseif(!$variablesReady)
        <a class="button button--ink" href="{{ route('admin.datasets.variables.index', $dataset) }}">Buka variabel</a>
    @elseif(!$dataReady)
        <a class="button button--ink" href="{{ route('admin.datasets.import.create', $dataset) }}">Isi data</a>
    @elseif(!$qualityReady)
        <a class="button button--ink" href="{{ route('admin.datasets.observations.index', $dataset) }}">Periksa angka</a>
    @elseif($dataset->status === 'published')
        <a class="button button--ink" href="{{ route('datasets.show', $dataset->slug) }}" target="_blank" rel="noopener">Lihat halaman publik ↗</a>
    @else
        <a class="button button--ink" href="#publication">Lihat kesiapan terbit</a>
    @endif
</section>

<div class="admin-edit-grid">
    <form id="metadata" class="panel form-panel" method="POST" action="{{ route('admin.datasets.update', $dataset) }}">
        @csrf
        @method('PUT')
        <div class="admin-section-heading"><span>01 / Pengaturan koleksi</span><h2>Identitas dan aturan akses</h2><p>Perubahan pada koleksi terbit akan mengembalikannya ke draft untuk diperiksa ulang.</p></div>
        @include('admin.datasets.form')
        <div class="form-actions"><button class="button button--ink" type="submit">Simpan perubahan</button></div>
    </form>
    <aside class="admin-rail">
        <section class="panel readiness" id="publication">
            <span class="panel-kicker">05 / Publikasi</span>
            <h2>Kesiapan terbit</h2>
            <p class="admin-readiness-note">Semua syarat berikut harus terpenuhi.</p>
            <ul>
                <li class="{{ $sourceReady ? 'is-done' : '' }}"><span></span>Sumber data aktif</li>
                <li class="{{ $variablesReady ? 'is-done' : '' }}"><span></span>Minimal satu variabel aktif</li>
                <li class="{{ $dataReady ? 'is-done' : '' }}"><span></span>Minimal satu angka untuk variabel aktif</li>
                <li class="{{ $qualityReady ? 'is-done' : '' }}"><span></span>Semua angka aktif sudah ditinjau</li>
            </ul>
            @if($dataset->status === 'published')
                <form method="POST" action="{{ route('admin.datasets.archive', $dataset) }}">@csrf<button class="button button--line button--block" type="submit">Arsipkan koleksi</button></form>
            @else
                <form method="POST" action="{{ route('admin.datasets.publish', $dataset) }}">@csrf<button class="button button--success button--block" type="submit" @disabled(!$readyToPublish)>Terbitkan koleksi</button></form>
                @if(!$readyToPublish)<small>Lengkapi langkah yang belum terpenuhi. Tombol aktif setelah data siap.</small>@endif
            @endif
        </section>
        <details class="panel admin-danger-details">
            <summary>Hapus koleksi</summary>
            <p>Tindakan ini menghapus variabel dan observasi. Koleksi dengan riwayat permintaan tidak dapat dihapus.</p>
            <form method="POST" action="{{ route('admin.datasets.destroy', $dataset) }}" data-confirm="Hapus koleksi beserta seluruh variabel dan observasinya?">@csrf @method('DELETE')<button type="submit">Hapus koleksi</button></form>
        </details>
    </aside>
</div>
@endsection