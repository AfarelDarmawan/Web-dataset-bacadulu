@extends('layouts.admin')
@section('title', 'Tambah Provider')
@section('page_title', 'Tambah provider')
@section('content')
<nav class="backline"><a href="{{ route('admin.providers.index') }}">← Provider data</a></nav>
<div class="page-head"><div><span class="eyebrow">New source owner</span><h1>Tambah provider</h1><p>Gunakan identitas resmi agar provenance dataset tetap jelas.</p></div></div>
<form class="panel form-panel" method="POST" action="{{ route('admin.providers.store') }}">@csrf @include('admin.providers.form')<div class="form-actions"><a class="button button--line" href="{{ route('admin.providers.index') }}">Batal</a><button class="button button--ink" type="submit">Simpan provider</button></div></form>
@endsection
