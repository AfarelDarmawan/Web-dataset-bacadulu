@extends('layouts.admin')
@section('title', 'Permintaan Akses')
@section('page_title', 'Permintaan akses')
@section('content')
<div class="page-head"><div><span class="eyebrow">Access governance</span><h1>Permintaan akses</h1><p>Tinjau identitas, tujuan, cakupan, dan estimasi keluaran sebelum memberi akses.</p></div></div>
<form class="toolbar" method="GET"><div class="field field--search"><label for="q">Cari permintaan</label><input id="q" name="q" value="{{ request('q') }}" placeholder="Nomor, pengguna, atau dataset"></div><div class="field"><label for="status">Status</label><select id="status" name="status"><option value="">Semua status</option>@foreach(['pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected'] as $value=>$label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></div><button class="button button--line" type="submit">Terapkan</button>@if(request()->hasAny(['q','status']))<a href="{{ route('admin.requests.index') }}">Reset</a>@endif</form>
<section class="panel panel--flush">
    @if($requests->isEmpty())
        <div class="panel-empty"><span>Tidak ada permintaan</span><p>Antrian akses akan muncul ketika peneliti mengajukan dataset terbatas atau berlisensi.</p></div>
    @else
        <div class="table-scroll"><table class="data-table admin-table"><thead><tr><th>Nomor</th><th>Pemohon</th><th>Dataset</th><th>Cakupan</th><th>Tanggal</th><th>Status</th><th></th></tr></thead><tbody>@foreach($requests as $item)<tr><td><code>{{ $item->request_number }}</code></td><td><strong>{{ $item->user->name }}</strong><small>{{ $item->user->institution ?: $item->user->email }}</small></td><td>{{ $item->dataset->title }}<small>{{ $item->dataset->provider->name }}</small></td><td>{{ count($item->variable_ids) }} variabel<small>{{ number_format($item->estimated_cells) }} sel</small></td><td>{{ $item->created_at->format('d M Y') }}<small>{{ $item->created_at->format('H:i') }}</small></td><td><span class="status status--{{ $item->status }}">{{ ucfirst($item->status) }}</span></td><td><a class="table-action" href="{{ route('admin.requests.show', $item) }}">Tinjau →</a></td></tr>@endforeach</tbody></table></div><div class="pagination-wrap pagination-wrap--panel">{{ $requests->links() }}</div>
    @endif
</section>
@endsection
