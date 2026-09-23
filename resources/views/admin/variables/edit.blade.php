@extends('layouts.admin')
@section('title', 'Edit Variabel')
@section('page_title', 'Edit variabel')
@section('content')
<nav class="backline"><a href="{{ route('admin.datasets.variables.index', $dataset) }}">← Kamus variabel</a></nav>
<div class="page-head"><div><span class="eyebrow">{{ $dataset->code }} / {{ $variable->code }}</span><h1>{{ $variable->name }}</h1><p>{{ number_format($variable->observations()->count()) }} observasi menggunakan variabel ini.</p></div></div>
<form class="panel form-panel" method="POST" action="{{ route('admin.datasets.variables.update', [$dataset, $variable]) }}">@csrf @method('PUT') @include('admin.variables.form')<div class="form-actions"><a class="button button--line" href="{{ route('admin.datasets.variables.index', $dataset) }}">Batal</a><button class="button button--ink" type="submit">Simpan perubahan</button></div></form>
@endsection
