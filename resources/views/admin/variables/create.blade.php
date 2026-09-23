@extends('layouts.admin')
@section('title', 'Tambah Variabel')
@section('page_title', 'Tambah variabel')
@section('content')
<nav class="backline"><a href="{{ route('admin.datasets.variables.index', $dataset) }}">← Kamus variabel</a></nav>
<div class="page-head"><div><span class="eyebrow">{{ $dataset->code }}</span><h1>Tambah variabel</h1><p>Definisi yang jelas membantu pengguna memilih data tanpa menebak makna kolom.</p></div></div>
<form class="panel form-panel" method="POST" action="{{ route('admin.datasets.variables.store', $dataset) }}">@csrf @include('admin.variables.form')<div class="form-actions"><a class="button button--line" href="{{ route('admin.datasets.variables.index', $dataset) }}">Batal</a><button class="button button--ink" type="submit">Simpan variabel</button></div></form>
@endsection
