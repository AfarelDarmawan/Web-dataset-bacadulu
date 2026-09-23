@extends('layouts.admin')
@section('title', 'Tambah Konektor BPS')
@section('page_title', 'Otomatisasi data')
@section('content')
<nav class="backline"><a href="{{ route('admin.automation.index', request('dataset') ? ['dataset' => request('dataset')] : []) }}">← Sinkronisasi BPS</a></nav>
<div class="page-head"><div><span class="eyebrow">Konektor baru</span><h1>Hubungkan WebAPI BPS.</h1><p>@if($selectedDataset)Pilih variabel di {{ $selectedDataset->title }} dan hubungkan dengan ID variabel BPS.@else Konfigurasi sekali, lalu sistem mengambil pembaruan ke staging secara terjadwal.@endif</p></div></div>
<form class="panel form-panel connector-form" method="POST" action="{{ route('admin.automation.connectors.store') }}">@csrf
    @include('admin.automation.form')
    <div class="form-actions"><a class="button button--line" href="{{ route('admin.automation.index', request('dataset') ? ['dataset' => request('dataset')] : []) }}">Batal</a><button class="button button--ink" type="submit">Simpan konektor</button></div>
</form>
@endsection