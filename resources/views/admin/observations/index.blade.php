@extends('layouts.admin')

@section('title', 'Quality Control '.$dataset->title)
@section('page_title', 'Kontrol kualitas')

@section('content')
<nav class="backline">
    <a href="{{ route('admin.datasets.edit', $dataset) }}">← {{ $dataset->title }}</a>
</nav>

<div class="page-head">
    <div>
        <span class="eyebrow">{{ $dataset->code }} / observasi</span>
        <h1>Periksa angka sebelum terbit.</h1>
        <p>Cocokkan nilai dengan sumber. Data yang belum ditinjau atau ditandai tidak dapat dipublikasikan.</p>
    </div>
    <a class="button button--line" href="{{ route('admin.datasets.import.create', $dataset) }}">Impor CSV lagi</a>
</div>

@if(request('source') === 'csv')
    <section class="admin-next-action" aria-labelledby="after-csv-title">
        <div>
            <span class="admin-next-action__label">CSV berhasil diproses · langkah 04</span>
            <h2 id="after-csv-title">Sekarang lakukan quality control.</h2>
            <p>Untuk setiap angka: pilih <strong>Reviewed</strong> bila sudah dicek, <strong>Verified</strong> bila sudah divalidasi, atau <strong>Flagged</strong> bila perlu diperbaiki.</p>
        </div>
        <a class="button button--ink" href="{{ route('admin.datasets.observations.index', [$dataset, 'quality' => 'unreviewed']) }}">Tampilkan yang belum ditinjau</a>
    </section>
@endif

<div class="quality-strip">
    @foreach(['unreviewed' => 'Belum ditinjau', 'reviewed' => 'Reviewed', 'verified' => 'Verified', 'flagged' => 'Flagged'] as $key => $label)
        <a class="quality-strip__{{ $key }} {{ request('quality') === $key ? 'is-active' : '' }}" href="{{ route('admin.datasets.observations.index', [$dataset, 'quality' => $key]) }}">
            <span>{{ $label }}</span>
            <strong>{{ number_format($qualityCounts[$key] ?? 0) }}</strong>
        </a>
    @endforeach
</div>

<form class="toolbar" method="GET">
    <div class="field field--search">
        <label for="q">Cari {{ $dataset->entityLabelLower() }} atau periode</label>
        <input id="q" name="q" value="{{ request('q') }}" placeholder="{{ $dataset->isCorporate() ? 'BBCA, Telkom, 2024...' : 'Indonesia, Jawa Barat, 2024...' }}">
    </div>

    <div class="field">
        <label for="variable">Variabel</label>
        <select id="variable" name="variable">
            <option value="">Semua variabel</option>
            @foreach($variables as $variable)
                <option value="{{ $variable->id }}" @selected((string) request('variable') === (string) $variable->id)>{{ $variable->code }} · {{ $variable->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="field">
        <label for="quality">Kualitas</label>
        <select id="quality" name="quality">
            <option value="">Semua status</option>
            @foreach(['unreviewed', 'reviewed', 'verified', 'flagged'] as $status)
                <option value="{{ $status }}" @selected(request('quality') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
    </div>

    <button class="button button--line" type="submit">Terapkan</button>
    @if(request()->hasAny(['q', 'variable', 'quality']))
        <a href="{{ route('admin.datasets.observations.index', $dataset) }}">Reset</a>
    @endif
</form>

<section class="panel panel--flush">
    @if($observations->isEmpty())
        <div class="panel-empty">
            <span>Tidak ada observasi pada tampilan ini</span>
            <p>{{ request()->hasAny(['q', 'variable', 'quality']) ? 'Ubah atau reset filter untuk melihat data lain.' : 'Impor CSV untuk menambahkan data pertama.' }}</p>
            @if(request()->hasAny(['q', 'variable', 'quality']))
                <a class="button button--line button--small" href="{{ route('admin.datasets.observations.index', $dataset) }}">Reset filter</a>
            @else
                <a class="button button--ink button--small" href="{{ route('admin.datasets.import.create', $dataset) }}">Impor observasi</a>
            @endif
        </div>
    @else
        <div class="table-scroll">
            <table class="data-table admin-table">
                <thead>
                    <tr>
                        <th>Variabel</th>
                        <th>{{ $dataset->entityLabel() }}</th>
                        <th>Periode</th>
                        <th>Nilai</th>
                        <th>Sumber</th>
                        <th>Keputusan QC</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($observations as $observation)
                        <tr>
                            <td><code>{{ $observation->variable->code }}</code><small>{{ $observation->variable->name }}</small></td>
                            <td>{{ $observation->geography_name }}<small>{{ $observation->geography_code }}</small></td>
                            <td>{{ $observation->period }}</td>
                            <td><strong>{{ $observation->displayValue() }}</strong><small>{{ $observation->variable->unit }}</small></td>
                            <td><small>{{ $observation->source_reference ?: '—' }}</small></td>
                            <td>
                                <form class="quality-form" method="POST" action="{{ route('admin.datasets.observations.update', [$dataset, $observation]) }}">
                                    @csrf
                                    @method('PATCH')
                                    <select name="quality_status" aria-label="Status kualitas">
                                        <option value="unreviewed" @selected($observation->quality_status === 'unreviewed')>Belum ditinjau</option>
                                        <option value="reviewed" @selected($observation->quality_status === 'reviewed')>Reviewed — sudah dicek</option>
                                        <option value="verified" @selected($observation->quality_status === 'verified')>Verified — sudah divalidasi</option>
                                        <option value="flagged" @selected($observation->quality_status === 'flagged')>Flagged — perlu diperbaiki</option>
                                    </select>
                                    <button type="submit">Simpan</button>
                                </form>
                            </td>
                            <td>
                                <form method="POST" action="{{ route('admin.datasets.observations.destroy', [$dataset, $observation]) }}" data-confirm="Hapus observasi ini?">
                                    @csrf
                                    @method('DELETE')
                                    <button class="table-action table-action--danger" type="submit">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap pagination-wrap--panel">{{ $observations->links() }}</div>
    @endif
</section>

@if($activeObservationCount === 0)
    <div class="admin-stage-next">
        <div><strong>Belum ada angka aktif.</strong><span>Impor CSV atau ambil data dari BPS sebelum melakukan quality control.</span></div>
        <a class="button button--ink" href="{{ route('admin.datasets.import.create', $dataset) }}">Isi data →</a>
    </div>
@elseif($pendingQualityCount > 0)
    <div class="admin-stage-next">
        <div><strong>Masih ada {{ number_format($pendingQualityCount) }} angka yang perlu ditangani.</strong><span>Selesaikan data Belum ditinjau dan Flagged. Publikasi masih dikunci.</span></div>
        <a class="button button--ink" href="{{ route('admin.datasets.observations.index', [$dataset, 'quality' => 'unreviewed']) }}">Lanjutkan pemeriksaan →</a>
    </div>
@else
    <div class="admin-stage-next">
        <div><strong>Quality control selesai.</strong><span>Semua angka aktif sudah Reviewed atau Verified. Koleksi siap diperiksa untuk publikasi.</span></div>
        <a class="button button--ink" href="{{ route('admin.datasets.edit', $dataset) }}#publication">Lanjut ke publikasi →</a>
    </div>
@endif
@endsection
