@extends('layouts.admin')
@section('title', 'Otomatisasi Data')
@section('page_title', 'Otomatisasi data')
@section('content')
@php($runLabels = ['queued'=>'Antre','processing'=>'Berjalan','review'=>'Perlu review','applied'=>'Diterapkan','rejected'=>'Ditolak','failed'=>'Gagal'])
<div class="page-head page-head--automation" data-motion-item>
    <div><span class="eyebrow">Pengisian data / BPS</span><h1>Sinkronisasi BPS</h1><p>@if($selectedDataset) Menampilkan konektor untuk {{ $selectedDataset->title }}. @else Pilih koleksi statistik wilayah, hubungkan variabel BPS, lalu periksa hasilnya sebelum diterapkan. @endif Data tidak langsung terbit.</p></div>
    <a class="button button--ink" href="{{ route('admin.automation.connectors.create', $selectedDataset ? ['dataset' => $selectedDataset->id] : []) }}">Tambah konektor BPS</a>
</div>
@if($selectedDataset)<div class="admin-filter-context">Hanya {{ $selectedDataset->title }} <a href="{{ route('admin.automation.index') }}">Lihat semua konektor</a></div>@endif
@if($selectedDataset)
<nav class="admin-input-options" aria-label="Cara mengisi data koleksi"><span>Pilih cara mengisi</span><a href="{{ route('admin.datasets.import.create', $selectedDataset) }}">Impor CSV</a><a class="is-current" href="{{ route('admin.automation.index', ['dataset' => $selectedDataset->id]) }}" aria-current="page">Sinkronisasi BPS</a><a href="{{ route('admin.ai.index', ['dataset' => $selectedDataset->id]) }}">Unggah dokumen</a></nav>
@endif

<div class="metric-strip">
    <div><span>Konektor aktif</span><strong data-count="{{ $stats['active'] }}">{{ number_format($stats['active']) }}</strong></div>
    <div class="metric-strip__attention"><span>Perlu review</span><strong data-count="{{ $stats['review'] }}">{{ number_format($stats['review']) }}</strong></div>
    <div><span>Sudah diterapkan</span><strong data-count="{{ $stats['applied'] }}">{{ number_format($stats['applied']) }}</strong></div>
    <div><span>Gagal</span><strong data-count="{{ $stats['failed'] }}">{{ number_format($stats['failed']) }}</strong></div>
</div>

@if(config('bacadulu.bps.mock'))
    <div class="config-warning config-warning--mock"><strong>Mode simulasi aktif</strong><p>Sistem menghasilkan enam baris contoh tanpa menghubungi BPS. Matikan <code>BACADULU_BPS_MOCK</code> sebelum production.</p></div>
@elseif(!config('bacadulu.bps.enabled') || !config('bacadulu.bps.api_key'))
    <div class="config-warning"><strong>WebAPI BPS belum diaktifkan</strong><p>Tambahkan <code>BPS_API_KEY</code> dan ubah <code>BACADULU_BPS_ENABLED=true</code> di <code>.env</code>, lalu jalankan <code>php artisan optimize:clear</code>. Konektor tetap dapat disiapkan sekarang.</p></div>
@else
    <div class="connector-ready"><span class="status-dot"></span><div><strong>WebAPI BPS siap digunakan</strong><small>Token tersedia pada konfigurasi server dan tidak pernah ditampilkan ke browser.</small></div></div>
@endif

<section class="panel panel--flush automation-registry">
    <div class="panel__head panel__head--padded"><div><span class="panel-kicker">Konektor sumber</span><h2>Sinkronisasi yang terdaftar</h2></div><small>{{ number_format($connectors->total()) }} konektor</small></div>
    @if($connectors->isEmpty())
        <div class="panel-empty panel-empty--borderless"><span>Belum ada konektor</span><p>Buat koleksi statistik wilayah dan variabel aktif, lalu hubungkan variabel BPS.</p><a class="button button--ink button--small" href="{{ route('admin.automation.connectors.create', $selectedDataset ? ['dataset' => $selectedDataset->id] : []) }}">Buat konektor pertama</a></div>
    @else
        <div class="table-scroll"><table class="data-table admin-table connector-table"><thead><tr><th>Konektor</th><th>Tujuan</th><th>Jadwal</th><th>Sinkronisasi terakhir</th><th>Status</th><th></th></tr></thead><tbody>@foreach($connectors as $connector)<tr><td><strong>{{ $connector->name }}</strong><small>BPS · domain {{ data_get($connector->config, 'domain') }} · var {{ data_get($connector->config, 'variable_id') }}</small></td><td>{{ $connector->variable->name }}<small>{{ $connector->dataset->title }}</small></td><td>{{ ['manual'=>'Manual','daily'=>'Harian','weekly'=>'Mingguan','monthly'=>'Bulanan'][$connector->schedule] ?? ucfirst($connector->schedule) }}<small>{{ $connector->next_sync_at ? 'Berikutnya '.$connector->next_sync_at->diffForHumans() : 'Tanpa jadwal otomatis' }}</small></td><td>{{ $connector->last_succeeded_at?->format('d M Y H:i') ?? 'Belum pernah' }}<small>{{ $connector->runs_count }} proses · {{ $connector->open_runs_count }} terbuka</small></td><td><span class="status status--{{ $connector->status }}">{{ $connector->status === 'active' ? 'Aktif' : 'Dijeda' }}</span>@if($connector->last_error)<small class="table-error">{{ $connector->last_error }}</small>@endif</td><td class="table-actions"><a href="{{ route('admin.automation.connectors.edit', $connector) }}">Atur</a>@if($connector->open_runs_count === 0 && $connector->isActive())<form method="POST" action="{{ route('admin.automation.connectors.sync', $connector) }}" data-busy-form data-busy-label="Mengambil…">@csrf<button type="submit">Sinkronkan</button></form>@endif<form method="POST" action="{{ route('admin.automation.connectors.toggle', $connector) }}">@csrf @method('PATCH')<button type="submit">{{ $connector->isActive() ? 'Jeda' : 'Aktifkan' }}</button></form></td></tr>@endforeach</tbody></table></div>
        <div class="pagination-wrap pagination-wrap--panel">{{ $connectors->links() }}</div>
    @endif
</section>

<section class="panel panel--flush automation-history">
    <div class="panel__head panel__head--padded"><div><span class="panel-kicker">Riwayat terbaru</span><h2>Aktivitas sinkronisasi</h2></div></div>
    @if($recentRuns->isEmpty())
        <div class="panel-empty panel-empty--borderless"><span>BELUM ADA AKTIVITAS</span><p>Riwayat muncul setelah konektor dijalankan.</p></div>
    @else
        <div class="request-list">@foreach($recentRuns as $run)<a href="{{ route('admin.automation.runs.show', [$run->connector, $run]) }}"><span class="request-list__number">#{{ $run->id }}</span><div><strong>{{ $run->connector->name }}</strong><small>{{ number_format($run->rows_count) }} baris · {{ $run->trigger === 'scheduled' ? 'Terjadwal' : 'Manual' }} · {{ $run->created_at->diffForHumans() }}</small></div><span class="status status--{{ $run->status }}">{{ $runLabels[$run->status] ?? ucfirst($run->status) }}</span></a>@endforeach</div>
    @endif
</section>
@endsection