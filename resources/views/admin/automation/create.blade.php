@extends('layouts.admin')

@section('title', 'Tambah Konektor BPS')
@section('page_title', 'Otomatisasi data')

@section('content')
<nav class="backline">
    <a href="{{ route('admin.automation.index', request('dataset') ? ['dataset' => request('dataset')] : []) }}">← Sinkronisasi BPS</a>
</nav>

<div class="page-head">
    <div>
        <span class="eyebrow">Konektor baru</span>
        <h1>Hubungkan WebAPI BPS.</h1>
        <p>
            @if($selectedDataset)
                Hubungkan salah satu variabel di {{ $selectedDataset->title }} dengan variabel dari WebAPI BPS.
            @else
                Pilih tujuan katalog, masukkan ID BPS, lalu jalankan sinkronisasi pertama ke staging.
            @endif
        </p>
    </div>
</div>

<section class="admin-next-action" aria-labelledby="bps-flow-title">
    <div>
        <span class="admin-next-action__label">Alur konektor BPS</span>
        <h2 id="bps-flow-title">Hubungkan → sinkronkan → tinjau → terapkan → QC.</h2>
        <p>Membuat konektor belum langsung mengisi katalog. Setiap hasil API masuk ke staging dan menunggu keputusan admin.</p>
    </div>
    <a class="button button--line" href="https://webapi.bps.go.id/documentation/" target="_blank" rel="noopener noreferrer">Dokumentasi BPS ↗</a>
</section>

<form class="panel form-panel connector-form" method="POST" action="{{ route('admin.automation.connectors.store') }}">
    @csrf

    @include('admin.automation.form')

    <div class="form-actions">
        <a class="button button--line" href="{{ route('admin.automation.index', request('dataset') ? ['dataset' => request('dataset')] : []) }}">Batal</a>
        <button class="button button--ink" type="submit" @disabled($datasets->isEmpty())>Simpan konektor</button>
    </div>
</form>
@endsection
