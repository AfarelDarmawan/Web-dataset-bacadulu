@extends('layouts.admin')
@section('title', 'Variabel '.$dataset->title)
@section('page_title', 'Kamus variabel')
@section('content')
<nav class="backline"><a href="{{ route('admin.datasets.edit', $dataset) }}">← {{ $dataset->title }}</a></nav>
<div class="page-head"><div><span class="eyebrow">{{ $dataset->code }} / schema</span><h1>Kamus variabel</h1><p>Tetapkan definisi, satuan, tipe nilai, tier, dan harga per sel sebelum mengimpor observasi.</p></div><a class="button button--ink" href="{{ route('admin.datasets.variables.create', $dataset) }}">Tambah variabel</a></div>
<section class="panel panel--flush">
    @if($variables->isEmpty())
        <div class="panel-empty"><span>Belum ada variabel</span><p>Observasi CSV hanya dapat dipetakan ke kode variabel yang sudah terdaftar.</p><a class="button button--ink button--small" href="{{ route('admin.datasets.variables.create', $dataset) }}">Tambah variabel pertama</a></div>
    @else
        <div class="table-scroll"><table class="data-table admin-table"><thead><tr><th>Kode</th><th>Variabel</th><th>Tipe & satuan</th><th>Akses</th><th>Observasi</th><th>Status</th><th></th></tr></thead><tbody>@foreach($variables as $variable)<tr><td><code>{{ $variable->code }}</code></td><td><strong>{{ $variable->name }}</strong><small>{{ \Illuminate\Support\Str::limit($variable->definition, 90) ?: 'Tanpa definisi' }}</small></td><td>{{ ucfirst($variable->data_type) }}<small>{{ $variable->unit ?: 'Tanpa satuan' }}</small></td><td>{{ ucfirst($variable->access_tier) }}<small>@if((float)$variable->price_per_cell > 0) Rp{{ number_format((float)$variable->price_per_cell, 0, ',', '.') }}/sel @else Tanpa biaya @endif</small></td><td>{{ number_format($variable->observations_count) }}<small>{{ number_format($variable->data_connectors_count) }} konektor</small></td><td><span class="status status--{{ $variable->is_active ? 'active' : 'inactive' }}">{{ $variable->is_active ? 'Aktif' : 'Nonaktif' }}</span></td><td class="table-actions"><a href="{{ route('admin.datasets.variables.edit', [$dataset, $variable]) }}">Edit</a>@if($variable->data_connectors_count > 0)<a href="{{ route('admin.automation.index') }}">Konektor</a>@else<form method="POST" action="{{ route('admin.datasets.variables.destroy', [$dataset, $variable]) }}" data-confirm="Hapus variabel ini?">@csrf @method('DELETE')<button type="submit">Hapus</button></form>@endif</td></tr>@endforeach</tbody></table></div><div class="pagination-wrap pagination-wrap--panel">{{ $variables->links() }}</div>
    @endif
</section>
@if($variables->total() > 0)
    <div class="admin-stage-next"><div><strong>Variabel sudah disiapkan?</strong><span>Isi angkanya dengan CSV, BPS, atau dokumen sumber.</span></div><a class="button button--ink" href="{{ route('admin.datasets.import.create', $dataset) }}">Lanjut isi data →</a></div>
@endif
@endsection