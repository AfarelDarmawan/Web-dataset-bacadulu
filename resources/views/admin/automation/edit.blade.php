@extends('layouts.admin')
@section('title', 'Edit '.$connector->name)
@section('page_title', 'Otomatisasi data')
@section('content')
<nav class="backline"><a href="{{ route('admin.automation.index', ['dataset' => $connector->dataset_id]) }}">← Sinkronisasi BPS koleksi ini</a></nav>
<div class="page-head"><div><span class="eyebrow">Konektor #{{ $connector->id }}</span><h1>Perbarui pemetaan BPS.</h1><p>{{ $connector->dataset->title }} · {{ $connector->variable->name }}</p></div></div>
<form class="panel form-panel connector-form" method="POST" action="{{ route('admin.automation.connectors.update', $connector) }}">@csrf @method('PUT')
    @include('admin.automation.form')
    <div class="form-actions"><a class="button button--line" href="{{ route('admin.automation.index', ['dataset' => $connector->dataset_id]) }}">Batal</a><button class="button button--ink" type="submit">Simpan perubahan</button></div>
</form>
@endsection