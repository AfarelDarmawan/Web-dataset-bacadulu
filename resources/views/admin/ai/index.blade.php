@extends('layouts.admin')

@section('title', 'Ekstraksi Dokumen')
@section('page_title', 'Ekstraksi dokumen')

@section('content')
<div class="page-intro page-intro--admin">
    <div><span class="eyebrow">Pengisian data / Dokumen</span><h1>Dokumen & ekstraksi</h1></div>
    <p>Unggah dokumen sumber, ekstrak kandidat observasi, lalu periksa hasilnya. AI hanya membantu membaca; keputusan dan publikasi tetap milik admin.</p>
</div>
@if($selectedDataset)<div class="admin-filter-context">Hanya {{ $selectedDataset->title }} <a href="{{ route('admin.ai.index') }}">Lihat semua dokumen</a></div>@endif
@if($selectedDataset)
<nav class="admin-input-options" aria-label="Cara mengisi data koleksi"><span>Pilih cara mengisi</span><a href="{{ route('admin.datasets.import.create', $selectedDataset) }}">Impor CSV</a>@if(!$selectedDataset->isCorporate())<a href="{{ route('admin.automation.index', ['dataset' => $selectedDataset->id]) }}">Sinkronisasi BPS</a>@endif<a class="is-current" href="{{ route('admin.ai.index', ['dataset' => $selectedDataset->id]) }}" aria-current="page">Unggah dokumen</a></nav>
@endif

<div class="metric-strip">
    <div><span>Dokumen privat</span><strong>{{ number_format($documents->total()) }}</strong></div>
    <div><span>Menunggu review</span><strong>{{ number_format($reviewCount) }}</strong></div>
    <div><span>Mode CSV</span><strong>Lokal</strong></div>
    <div class="{{ config('bacadulu.ai.enabled') && config('bacadulu.ai.api_key') ? '' : 'metric-strip__attention' }}"><span>OpenAI</span><strong>{{ config('bacadulu.ai.enabled') && config('bacadulu.ai.api_key') ? 'Aktif' : 'Belum' }}</strong></div>
</div>

<div class="ai-ingestion-grid">
    <form class="panel upload-panel" method="POST" action="{{ route('admin.source-documents.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="panel__head"><div><span class="panel-kicker">01 / Sumber</span><h2>Unggah dokumen sumber</h2></div><span class="private-chip">Penyimpanan privat</span></div>
        @if($datasets->isEmpty())
            <div class="inline-empty">Buat provider dan dataset terlebih dahulu sebelum mengunggah sumber.</div>
            <a class="button button--ink button--block" href="{{ route('admin.datasets.create') }}">Buat dataset</a>
        @else
            <div class="field"><label for="dataset_id">Dataset tujuan</label><select id="dataset_id" name="dataset_id" required><option value="">Pilih dataset</option>@foreach($datasets as $dataset)<option value="{{ $dataset->id }}" @selected((string) old('dataset_id', request('dataset')) === (string) $dataset->id)>{{ $dataset->code }} — {{ $dataset->title }} / {{ $dataset->provider->name }}</option>@endforeach</select><small>Hasil ekstraksi tidak dapat berpindah dataset setelah review dimulai.</small></div>
            <label class="drop-field drop-field--compact" for="source_file"><span class="drop-field__icon">SRC</span><strong>Pilih PDF, CSV, XLS, atau XLSX</strong><small>Maksimal {{ number_format(config('bacadulu.ai.max_kilobytes') / 1024) }} MB. File disimpan di disk privat.</small><input id="source_file" name="file" type="file" accept=".pdf,.csv,.txt,.xls,.xlsx" required data-file-input><b data-file-name>Belum ada file dipilih</b></label>
            <button class="button button--ink button--block" type="submit">Simpan dokumen privat</button>
        @endif
    </form>

    <section class="panel ingestion-protocol">
        <div class="panel__head"><div><span class="panel-kicker">Protokol</span><h2>Empat gerbang sebelum terbit</h2></div></div>
        <ol class="protocol-list">
            <li><b>01</b><div><strong>Kunci sumber</strong><span>Hash SHA-256, pemeriksaan signature, dan penyimpanan privat.</span></div></li>
            <li><b>02</b><div><strong>Staging ekstraksi</strong><span>PDF/XLSX diproses model; CSV standar dapat dibaca lokal.</span></div></li>
            <li><b>03</b><div><strong>Review manusia</strong><span>Admin memeriksa nilai, periode, unit, dan bukti halaman/sheet.</span></div></li>
            <li><b>04</b><div><strong>QC dan publikasi</strong><span>Observasi masuk sebagai belum ditinjau; publikasi tetap melalui gerbang kualitas.</span></div></li>
        </ol>
        <div class="security-note"><strong>Batas AI</strong><p>Model hanya mengusulkan. Ia tidak dapat menyetujui baris, mengubah status QC, atau menerbitkan dataset.</p></div>
    </section>
</div>

<section class="panel panel--flush ai-section">
    <div class="panel__head panel__head--padded"><div><span class="panel-kicker">Antrean review</span><h2>Ekstraksi terbaru</h2></div></div>
    @if($recentJobs->isEmpty())
        <div class="panel-empty panel-empty--borderless"><span>Belum ada ekstraksi</span><p>Unggah satu dokumen sumber untuk memulai.</p></div>
    @else
        @php($jobLabels = ['queued'=>'Dalam antrean','processing'=>'Sedang diproses','review'=>'Perlu review','approved'=>'Diterapkan','rejected'=>'Ditolak','failed'=>'Gagal'])
        <div class="table-scroll"><table class="data-table admin-table"><thead><tr><th>Job</th><th>Dokumen / dataset</th><th>Metode</th><th>Kandidat</th><th>Status</th><th></th></tr></thead><tbody>@foreach($recentJobs as $job)<tr><td><strong>#{{ $job->id }}</strong><small>{{ $job->created_at->format('d M Y, H:i') }}</small></td><td><strong>{{ $job->sourceDocument->original_name }}</strong><small>{{ $job->dataset->code }} · {{ $job->dataset->title }}</small></td><td>{{ $job->extractor === 'local_csv' ? 'CSV lokal' : $job->model }}</td><td>{{ number_format($job->rows_count) }}<small>{{ number_format($job->proposed_rows_count) }} belum diputuskan</small></td><td><span class="status status--{{ $job->status }}">{{ $jobLabels[$job->status] ?? ucfirst($job->status) }}</span></td><td><a class="table-action" href="{{ route('admin.ai-extractions.show', $job) }}">Buka →</a></td></tr>@endforeach</tbody></table></div>
    @endif
</section>

<section class="panel panel--flush ai-section">
    <div class="panel__head panel__head--padded"><div><span class="panel-kicker">Registry sumber</span><h2>Dokumen sumber</h2></div></div>
    @if($documents->isEmpty())
        <div class="panel-empty panel-empty--borderless"><span>Registry sumber masih kosong</span><p>Dokumen yang diunggah akan muncul di sini bersama checksum dan jejak ekstraksinya.</p></div>
    @else
        <div class="table-scroll"><table class="data-table admin-table"><thead><tr><th>Dokumen</th><th>Dataset</th><th>Ukuran</th><th>Checksum</th><th>Job</th><th>Status</th><th></th></tr></thead><tbody>@foreach($documents as $document)<tr><td><strong>{{ $document->original_name }}</strong><small>{{ strtoupper($document->extension) }} · {{ $document->created_at->format('d M Y') }}</small></td><td><strong>{{ $document->dataset->title }}</strong><small>{{ $document->dataset->provider->name }}</small></td><td>{{ number_format($document->size_bytes / 1024, 1) }} KB</td><td><code>{{ substr($document->sha256, 0, 12) }}…</code></td><td>{{ $document->extraction_jobs_count }}</td><td><span class="status status--{{ $document->status }}">{{ ucfirst($document->status) }}</span></td><td><a class="table-action" href="{{ route('admin.source-documents.show', $document) }}">Periksa →</a></td></tr>@endforeach</tbody></table></div>
        <div class="pagination-wrap pagination-wrap--panel">{{ $documents->links() }}</div>
    @endif
</section>
@endsection