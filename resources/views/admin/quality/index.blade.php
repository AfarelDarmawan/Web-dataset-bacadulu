@extends('layouts.admin')

@section('title', 'Pusat Quality Control')
@section('page_title', 'Pusat quality control')

@section('content')
<div class="page-head" data-motion-item>
    <div>
        <span class="eyebrow">Review sebelum publikasi</span>
        <h1>Satu antrean untuk semua pengecualian.</h1>
        <p>Sistem menangani pengambilan dan penyusunan awal. Admin cukup membuka data yang perlu keputusan, bukti, atau perbaikan.</p>
    </div>
    <a class="button button--line" href="{{ route('admin.datasets.index', ['status' => 'draft']) }}">Lihat seluruh draft</a>
</div>

<div class="quality-command-grid">
    <a class="quality-command {{ $counts['sync_review'] > 0 ? 'has-work' : '' }}" href="{{ route('admin.automation.index') }}">
        <span>01 / Sinkronisasi</span><strong>{{ number_format($counts['sync_review']) }}</strong><p>Hasil BPS menunggu keputusan.</p><b>Buka antrean →</b>
    </a>
    <a class="quality-command {{ $counts['extraction_review'] > 0 ? 'has-work' : '' }}" href="{{ route('admin.ai.index') }}">
        <span>02 / Ekstraksi</span><strong>{{ number_format($counts['extraction_review']) }}</strong><p>Dokumen memiliki kandidat data.</p><b>Periksa hasil →</b>
    </a>
    <div class="quality-command {{ $counts['unreviewed'] > 0 ? 'has-work' : '' }}">
        <span>03 / Observasi baru</span><strong>{{ number_format($counts['unreviewed']) }}</strong><p>Nilai belum melewati QC dasar.</p><b>Pilih dataset di bawah</b>
    </div>
    <div class="quality-command quality-command--danger {{ $counts['flagged'] > 0 ? 'has-work' : '' }}">
        <span>04 / Ditandai</span><strong>{{ number_format($counts['flagged']) }}</strong><p>Nilai memiliki masalah atau kejanggalan.</p><b>Prioritas utama</b>
    </div>
    <a class="quality-command" href="{{ route('admin.datasets.index', ['status' => 'draft']) }}">
        <span>05 / Draft</span><strong>{{ number_format($counts['drafts']) }}</strong><p>Koleksi belum atau perlu diterbitkan ulang.</p><b>Kelola draft →</b>
    </a>
    <a class="quality-command {{ $counts['pending_requests'] > 0 ? 'has-work' : '' }}" href="{{ route('admin.requests.index', ['status' => 'pending']) }}">
        <span>06 / Permintaan</span><strong>{{ number_format($counts['pending_requests']) }}</strong><p>Pengguna menunggu keputusan akses.</p><b>Tinjau permintaan →</b>
    </a>
</div>

<div class="quality-layout">
    <section class="panel panel--flush quality-layout__main">
        <div class="panel__head panel__head--padded"><div><span class="panel-kicker">Observasi</span><h2>Dataset yang perlu diperiksa</h2></div><small>Ditandai didahulukan</small></div>
        @if($datasetIssues->isEmpty())
            <div class="panel-empty panel-empty--borderless"><span>Antrean observasi bersih</span><p>Tidak ada observasi belum ditinjau atau ditandai.</p></div>
        @else
            <div class="request-list quality-dataset-list">
                @foreach($datasetIssues as $dataset)
                    <a href="{{ route('admin.datasets.observations.index', $dataset) }}">
                        <span class="request-list__number">{{ $dataset->code }}</span>
                        <div><strong>{{ $dataset->title }}</strong><small>{{ $dataset->provider->name }} · {{ $dataset->scopeLabel() }}</small></div>
                        <span><b>{{ number_format($dataset->flagged_count) }} ditandai</b><small>{{ number_format($dataset->unreviewed_count) }} belum ditinjau</small></span>
                    </a>
                @endforeach
            </div>
        @endif
    </section>

    <aside class="quality-layout__rail">
        <section class="panel quality-queue-panel">
            <span class="panel-kicker">Hasil otomatisasi</span><h2>Menunggu review</h2>
            @if($reviewSyncs->isEmpty() && $reviewExtractions->isEmpty())
                <p class="muted-copy">Tidak ada staging sinkronisasi atau ekstraksi yang menunggu.</p>
            @else
                <div class="quality-mini-list">
                    @foreach($reviewSyncs as $run)
                        <a href="{{ route('admin.automation.runs.show', [$run->connector, $run]) }}"><strong>BPS · {{ $run->connector->name }}</strong><small>{{ number_format($run->rows_count) }} baris · {{ $run->created_at->diffForHumans() }}</small></a>
                    @endforeach
                    @foreach($reviewExtractions as $job)
                        <a href="{{ route('admin.ai-extractions.show', $job) }}"><strong>Dokumen · {{ $job->sourceDocument->original_name }}</strong><small>{{ $job->dataset->title }} · {{ number_format($job->rows_count) }} kandidat</small></a>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="panel quality-queue-panel">
            <span class="panel-kicker">Publication gate</span><h2>Draft terbaru</h2>
            @if($draftDatasets->isEmpty())
                <p class="muted-copy">Tidak ada dataset berstatus draft.</p>
            @else
                <div class="quality-mini-list">
                    @foreach($draftDatasets as $dataset)
                        <a href="{{ route('admin.datasets.edit', $dataset) }}"><strong>{{ $dataset->title }}</strong><small>{{ $dataset->variables_count }} variabel · {{ number_format($dataset->observations_count) }} observasi</small></a>
                    @endforeach
                </div>
            @endif
        </section>
    </aside>
</div>
@endsection
