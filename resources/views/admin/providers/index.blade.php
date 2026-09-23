@extends('layouts.admin')

@section('title', 'Provider Data')
@section('page_title', 'Provider data')

@section('content')
<div class="page-head"><div><span class="eyebrow">Source ownership</span><h1>Provider data</h1><p>Catat lembaga pemilik atau penerbit data sebelum membuat dataset.</p></div><a class="button button--ink" href="{{ route('admin.providers.create') }}">Tambah provider</a></div>
<form class="toolbar" method="GET"><div class="field field--search"><label for="q">Cari provider</label><input id="q" name="q" value="{{ request('q') }}" placeholder="Nama lembaga"></div><button class="button button--line" type="submit">Cari</button>@if(request('q'))<a href="{{ route('admin.providers.index') }}">Reset</a>@endif</form>
<section class="panel panel--flush">
    @if($providers->isEmpty())
        <div class="panel-empty"><span>Belum ada provider</span><p>Provider menjadi pemilik sumber untuk setiap dataset dalam katalog.</p><a class="button button--ink button--small" href="{{ route('admin.providers.create') }}">Tambah provider pertama</a></div>
    @else
        <div class="table-scroll"><table class="data-table admin-table"><thead><tr><th>Provider</th><th>Tipe</th><th>Kontak</th><th>Dataset</th><th>Status</th><th></th></tr></thead><tbody>@foreach($providers as $provider)<tr><td><strong>{{ $provider->name }}</strong><small>{{ $provider->website ?: $provider->slug }}</small></td><td>{{ ucfirst($provider->type) }}</td><td>{{ $provider->contact_name ?: '—' }}<small>{{ $provider->contact_email }}</small></td><td>{{ $provider->datasets_count }}</td><td><span class="status status--{{ $provider->status }}">{{ ucfirst($provider->status) }}</span></td><td class="table-actions"><a href="{{ route('admin.providers.edit', $provider) }}">Edit</a><form method="POST" action="{{ route('admin.providers.destroy', $provider) }}" data-confirm="Hapus provider ini?">@csrf @method('DELETE')<button type="submit">Hapus</button></form></td></tr>@endforeach</tbody></table></div><div class="pagination-wrap pagination-wrap--panel">{{ $providers->links() }}</div>
    @endif
</section>
@endsection
