@extends('layouts.admin')
@section('title', 'Edit Provider')
@section('page_title', 'Edit provider')
@section('content')
<nav class="backline"><a href="{{ route('admin.providers.index') }}">← Provider data</a></nav>
<div class="page-head"><div><span class="eyebrow">{{ $provider->slug }}</span><h1>{{ $provider->name }}</h1><p>{{ $provider->datasets()->count() }} dataset terhubung dengan provider ini.</p></div></div>
<form class="panel form-panel" method="POST" action="{{ route('admin.providers.update', $provider) }}">@csrf @method('PUT') @include('admin.providers.form')<div class="form-actions"><a class="button button--line" href="{{ route('admin.providers.index') }}">Batal</a><button class="button button--ink" type="submit">Simpan perubahan</button></div></form>
@endsection
