@extends('layouts.admin')
@section('title', 'Buat Dataset')
@section('page_title', 'Buat dataset')
@section('content')
<nav class="backline"><a href="{{ route('admin.datasets.index') }}">← Registry dataset</a></nav>
<div class="page-head"><div><span class="eyebrow">Entri katalog baru</span><h1>Tambah dataset</h1><p>Isi metadata dasarnya dahulu. Dataset disimpan sebagai draft dan belum terlihat di situs publik.</p></div></div>
@if($providers->isEmpty())
    <div class="alert alert--danger"><span>Belum ada penyedia data aktif. <a href="{{ route('admin.providers.create') }}">Tambahkan penyedia terlebih dahulu.</a></span></div>
@else
    <form class="panel form-panel" method="POST" action="{{ route('admin.datasets.store') }}">@csrf @include('admin.datasets.form')<div class="form-actions"><a class="button button--line" href="{{ route('admin.datasets.index') }}">Batal</a><button class="button button--ink" type="submit">Simpan sebagai draft</button></div></form>
@endif
@endsection
