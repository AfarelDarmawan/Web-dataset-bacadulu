@extends('layouts.admin')
@section('title', 'Quality Control '.$dataset->title)
@section('page_title', 'Kontrol kualitas')
@section('content')
<nav class="backline"><a href="{{ route('admin.datasets.edit', $dataset) }}">← {{ $dataset->title }}</a></nav>
<div class="page-head"><div><span class="eyebrow">{{ $dataset->code }} / observasi</span><h1>Kontrol kualitas</h1><p>Periksa observasi, tandai anomali, dan verifikasi data sebelum publikasi.</p></div><a class="button button--line" href="{{ route('admin.datasets.import.create', $dataset) }}">Impor CSV</a></div>

<div class="quality-strip">
    @foreach(['unreviewed'=>'Belum ditinjau','reviewed'=>'Reviewed','verified'=>'Verified','flagged'=>'Flagged'] as $key=>$label)
        <a class="quality-strip__{{ $key }} {{ request('quality') === $key ? 'is-active' : '' }}" href="{{ route('admin.datasets.observations.index', [$dataset, 'quality' => $key]) }}"><span>{{ $label }}</span><strong>{{ number_format($qualityCounts[$key] ?? 0) }}</strong></a>
    @endforeach
</div>

<form class="toolbar" method="GET"><div class="field field--search"><label for="q">Cari {{ $dataset->entityLabelLower() }} atau periode</label><input id="q" name="q" value="{{ request('q') }}" placeholder="{{ $dataset->isCorporate() ? 'BBCA, Telkom, 2024...' : 'Indonesia, Jawa Barat, 2024...' }}"></div><div class="field"><label for="variable">Variabel</label><select id="variable" name="variable"><option value="">Semua variabel</option>@foreach($variables as $variable)<option value="{{ $variable->id }}" @selected((string)request('variable') === (string)$variable->id)>{{ $variable->code }} · {{ $variable->name }}</option>@endforeach</select></div><div class="field"><label for="quality">Kualitas</label><select id="quality" name="quality"><option value="">Semua status</option>@foreach(['unreviewed','reviewed','verified','flagged'] as $status)<option value="{{ $status }}" @selected(request('quality') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div><button class="button button--line" type="submit">Terapkan</button>@if(request()->hasAny(['q','variable','quality']))<a href="{{ route('admin.datasets.observations.index', $dataset) }}">Reset</a>@endif</form>

<section class="panel panel--flush">
    @if($observations->isEmpty())
        <div class="panel-empty"><span>Tidak ada observasi</span><p>Impor CSV atau ubah filter untuk melihat data.</p><a class="button button--ink button--small" href="{{ route('admin.datasets.import.create', $dataset) }}">Impor observasi</a></div>
    @else
        <div class="table-scroll"><table class="data-table admin-table"><thead><tr><th>Variabel</th><th>{{ $dataset->entityLabel() }}</th><th>Periode</th><th>Nilai</th><th>Sumber</th><th>Quality status</th><th></th></tr></thead><tbody>@foreach($observations as $observation)<tr><td><code>{{ $observation->variable->code }}</code><small>{{ $observation->variable->name }}</small></td><td>{{ $observation->geography_name }}<small>{{ $observation->geography_code }}</small></td><td>{{ $observation->period }}</td><td><strong>{{ $observation->displayValue() }}</strong><small>{{ $observation->variable->unit }}</small></td><td><small>{{ $observation->source_reference ?: '—' }}</small></td><td><form class="quality-form" method="POST" action="{{ route('admin.datasets.observations.update', [$dataset, $observation]) }}">@csrf @method('PATCH')<select name="quality_status" aria-label="Status kualitas">@foreach(['unreviewed','reviewed','verified','flagged'] as $status)<option value="{{ $status }}" @selected($observation->quality_status === $status)>{{ ucfirst($status) }}</option>@endforeach</select><button type="submit">Simpan</button></form></td><td><form method="POST" action="{{ route('admin.datasets.observations.destroy', [$dataset, $observation]) }}" data-confirm="Hapus observasi ini?">@csrf @method('DELETE')<button class="table-action table-action--danger" type="submit">Hapus</button></form></td></tr>@endforeach</tbody></table></div><div class="pagination-wrap pagination-wrap--panel">{{ $observations->links() }}</div>
    @endif
</section>
<div class="admin-stage-next"><div><strong>Selesai memeriksa angka?</strong><span>Lihat syarat terbit dan publikasikan koleksi setelah seluruh angka aktif ditinjau.</span></div><a class="button button--ink" href="{{ route('admin.datasets.edit', $dataset) }}#publication">Periksa kesiapan terbit →</a></div>
@endsection