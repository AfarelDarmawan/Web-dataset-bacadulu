@extends('layouts.admin')
@section('title', 'Impor Observasi')
@section('page_title', 'Impor observasi')
@section('content')
<nav class="backline"><a href="{{ route('admin.datasets.edit', $dataset) }}">← {{ $dataset->title }}</a></nav>
<div class="page-head"><div><span class="eyebrow">CSV ingestion / {{ $dataset->scopeLabel() }}</span><h1>Impor observasi</h1><p>Baris dengan kombinasi variabel, {{ $dataset->entityLabelLower() }}, dan periode yang sama akan diperbarui, bukan digandakan.</p></div><div class="page-head__actions"><a class="button button--line" href="{{ asset('assets/dataset-import-template.csv') }}" download>Template kosong</a><a class="button button--line" href="{{ asset($dataset->isCorporate() ? 'assets/dataset-import-example-corporate-esg.csv' : 'assets/dataset-import-example-bps.csv') }}" download>Contoh {{ $dataset->isCorporate() ? 'perusahaan & ESG' : 'BPS/wilayah' }}</a></div></div>
<nav class="admin-input-options" aria-label="Cara mengisi data koleksi">
    <span>Pilih cara mengisi</span>
    <a class="is-current" href="{{ route('admin.datasets.import.create', $dataset) }}" aria-current="page">Impor CSV</a>
    @if(!$dataset->isCorporate())<a href="{{ route('admin.automation.index', ['dataset' => $dataset->id]) }}">Sinkronisasi BPS</a>@endif
    <a href="{{ route('admin.ai.index', ['dataset' => $dataset->id]) }}">Unggah dokumen</a>
</nav>
<div class="import-grid">
    <form class="panel upload-panel" method="POST" action="{{ route('admin.datasets.import.store', $dataset) }}" enctype="multipart/form-data">@csrf
        <label class="drop-field" for="file"><span class="drop-field__icon">CSV</span><strong>Pilih file observasi</strong><small>Maksimal {{ number_format(config('bacadulu.catalog.import_max_kilobytes') / 1024) }} MB. Pemisah koma atau titik koma.</small><input id="file" name="file" type="file" accept=".csv,.txt,text/csv" required data-file-input><b data-file-name>Belum ada file dipilih</b></label>
        <button class="button button--ink button--block" type="submit">Validasi dan impor</button>
    </form>
    <section class="panel import-guide"><span class="panel-kicker">Required schema</span><h2>Struktur kolom</h2><div class="schema-list"><div><code>variable_code</code><span>Wajib, harus cocok dengan kode variabel.</span></div><div><code>geography_code</code><span>Wajib, kode {{ $dataset->entityLabelLower() }}.</span></div><div><code>geography_name</code><span>Wajib, nama {{ $dataset->entityLabelLower() }}.</span></div><div><code>period</code><span>Wajib, misalnya 2024 atau 2024-Q1.</span></div><div><code>value</code><span>Wajib, angka atau teks.</span></div><div><code>source_reference</code><span>Opsional, halaman atau URL sumber.</span></div><div><code>quality_status</code><span>Opsional: unreviewed, reviewed, verified, flagged. Nilai kosong/tidak dikenali menjadi unreviewed.</span></div></div><p class="form-hint">Nama kolom teknis tetap <code>geography_*</code> agar CSV lama kompatibel. Untuk data perusahaan, isi dengan kode emiten/entitas dan nama perusahaan.</p></section>
</div>
@endsection