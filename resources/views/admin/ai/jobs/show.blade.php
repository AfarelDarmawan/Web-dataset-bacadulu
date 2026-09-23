@extends('layouts.admin')

@section('title', 'Review Ekstraksi #'.$job->id)
@section('page_title', 'Review hasil ekstraksi')

@section('content')
@php($jobLabel = ['queued'=>'Dalam antrean','processing'=>'Sedang diproses','review'=>'Perlu review','approved'=>'Diterapkan','rejected'=>'Ditolak','failed'=>'Gagal'][$job->status] ?? ucfirst($job->status))
<nav class="backline"><a href="{{ route('admin.source-documents.show', $job->sourceDocument) }}">← {{ $job->sourceDocument->original_name }}</a></nav>
<div class="dataset-admin-head extraction-job-head">
    <div><span class="eyebrow">Job #{{ $job->id }} / {{ $job->dataset->code }}</span><h1>Review kandidat data</h1><p>{{ $job->dataset->title }} · {{ $job->extractor === 'local_csv' ? 'CSV lokal' : $job->model }}</p></div>
    <span class="status status--large status--{{ $job->status }}">{{ $jobLabel }}</span>
</div>

<div class="extraction-summary">
    <section class="panel extraction-brief"><span class="panel-kicker">Catatan ekstraksi</span><h2>Ringkasan dokumen</h2><p>{{ $job->summary ?: 'Belum ada ringkasan untuk proses ini.' }}</p>@if($job->response_id)<small>ID respons: <code>{{ $job->response_id }}</code></small>@endif</section>
    <dl class="extraction-facts">
        <div><dt>Total kandidat</dt><dd>{{ number_format($rows->total()) }}</dd></div>
        <div><dt>Keyakinan model</dt><dd>{{ $job->overall_confidence !== null ? number_format((float) $job->overall_confidence * 100, 0).'%' : '—' }}</dd></div>
        <div><dt>Invalid</dt><dd>{{ number_format($validationCounts['invalid'] ?? 0) }}</dd></div>
        <div><dt>Belum diputuskan</dt><dd>{{ number_format($counts['proposed'] ?? 0) }}</dd></div>
    </dl>
</div>

@if(in_array($job->status, ['queued', 'processing'], true))
    <section class="panel processing-panel">
        <div class="processing-note"><span class="processing-note__dot"></span><div><strong>{{ $jobLabel }}</strong><small>Proses dimulai {{ $job->started_at?->diffForHumans() ?? $job->created_at->diffForHumans() }}.</small></div></div>
        <p>Jangan memulai ekstraksi kedua untuk dokumen yang sama. Muat ulang halaman untuk melihat hasil terbaru.</p>
        @if($job->isStale())
            <div class="config-warning"><strong>Proses melewati batas aman</strong><p>Pulihkan status job agar dokumen dapat dicoba ulang tanpa membuat proses ganda.</p></div>
            <form method="POST" action="{{ route('admin.ai-extractions.recover', $job) }}" data-confirm="Tandai proses ini gagal agar dokumen dapat diekstrak ulang?">@csrf<button class="button button--danger" type="submit">Pulihkan proses macet</button></form>
        @endif
    </section>
@elseif($job->status === 'failed')
    <section class="panel failure-panel"><span class="panel-kicker">{{ $job->error_code }}</span><h2>Ekstraksi tidak selesai</h2><p>{{ $job->error_message }}</p><a class="button button--line" href="{{ route('admin.source-documents.show', $job->sourceDocument) }}">Kembali ke dokumen</a></section>
@else
    <form class="toolbar" method="GET" action="{{ route('admin.ai-extractions.show', $job) }}">
        <div class="field"><label for="status">Keputusan</label><select id="status" name="status"><option value="">Semua</option>@foreach(['proposed'=>'Belum diputuskan','accepted'=>'Diterima','rejected'=>'Ditolak','applied'=>'Sudah diterapkan'] as $value=>$label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></div>
        <div class="field"><label for="validation">Validasi</label><select id="validation" name="validation"><option value="">Semua</option>@foreach(['valid'=>'Valid','warning'=>'Peringatan','invalid'=>'Tidak valid'] as $value=>$label)<option value="{{ $value }}" @selected(request('validation') === $value)>{{ $label }}</option>@endforeach</select></div>
        <button class="button button--ink button--small" type="submit">Terapkan filter</button>
        <a href="{{ route('admin.ai-extractions.show', $job) }}">Reset</a>
    </form>

    @if($job->isReviewable())
    <form id="bulk-review" method="POST" action="{{ route('admin.ai-extractions.rows.bulk', $job) }}">@csrf
        <div class="review-commandbar">
            <div><strong>Keputusan massal</strong><span>Pilih baris pada halaman ini, atau proses seluruh kandidat berdasarkan hasil validasi.</span></div>
            <div><button name="action" value="accept_selected" type="submit">Terima pilihan</button><button name="action" value="reject_selected" type="submit">Tolak pilihan</button><button class="command-positive" name="action" value="accept_all_valid" type="submit">Terima semua valid/warning</button><button class="command-negative" name="action" value="reject_all_invalid" type="submit">Tolak semua invalid</button></div>
        </div>
    @endif

    <section class="panel panel--flush extraction-table-panel">
        @if($rows->isEmpty())
            <div class="panel-empty panel-empty--borderless"><span>Tidak ada kandidat</span><p>Ubah filter atau kembali ke dokumen sumber.</p></div>
        @else
            <div class="table-scroll"><table class="data-table admin-table extraction-table"><thead><tr>@if($job->isReviewable())<th><label class="table-check"><input type="checkbox" data-review-check-all aria-label="Pilih semua kandidat pada halaman ini"></label></th>@endif<th>#</th><th>Variabel</th><th>Entitas / periode</th><th>Nilai</th><th>Bukti sumber</th><th>Confidence</th><th>Validasi</th><th>Keputusan</th><th></th></tr></thead><tbody>@foreach($rows as $row)<tr class="extraction-row extraction-row--{{ $row->validation_status }}">@if($job->isReviewable())<td>@if($row->status === 'proposed')<label class="table-check"><input type="checkbox" name="row_ids[]" value="{{ $row->id }}" data-review-checkbox aria-label="Pilih kandidat baris {{ $row->row_index }}"></label>@endif</td>@endif<td><code>{{ $row->row_index }}</code></td><td><strong>{{ $row->variable_code }}</strong><small>{{ $row->variable_name }}@if($row->unit) · {{ $row->unit }}@endif</small>@if($row->matchedVariable)<span class="match-chip">Kamus cocok</span>@else<span class="match-chip match-chip--new">Variabel baru</span>@endif</td><td><strong>{{ $row->geography_name }}</strong><small>{{ $row->geography_code }} · {{ $row->period }}</small></td><td><strong>{{ $row->value_numeric !== null ? rtrim(rtrim(number_format((float) $row->value_numeric, 6, '.', ','), '0'), '.') : $row->value_text }}</strong><small>{{ $row->data_type }}</small></td><td><strong>{{ $row->source_locator }}</strong>@if($row->source_excerpt)<small class="source-excerpt">{{ $row->source_excerpt }}</small>@endif</td><td><span class="confidence-meter"><progress max="100" value="{{ max(0, min(100, (float) $row->confidence * 100)) }}">{{ number_format((float) $row->confidence * 100, 0) }}%</progress><b>{{ number_format((float) $row->confidence * 100, 0) }}%</b></span></td><td><span class="mini-status mini-status--{{ $row->validation_status }}">{{ $row->validation_status }}</span>@if($row->validation_issues)<small class="issue-copy">{{ implode(' ', $row->validation_issues) }}</small>@endif</td><td><span class="status status--{{ $row->status }}">{{ $row->status === 'proposed' ? 'Belum' : ucfirst($row->status) }}</span></td><td>@if($job->isReviewable())<a class="table-action" href="{{ route('admin.ai-extractions.rows.edit', [$job, $row]) }}">Edit →</a>@endif</td></tr>@endforeach</tbody></table></div>
            <div class="pagination-wrap pagination-wrap--panel">{{ $rows->links() }}</div>
        @endif
    </section>
    @if($job->isReviewable())</form>@endif

    @if($job->isReviewable())
    <div class="finalize-grid">
        <section class="panel finalize-panel"><span class="panel-kicker">04 / Terapkan ke draft</span><h2>Finalisasi ke observasi</h2><p>Finalisasi hanya tersedia setelah seluruh kandidat diputuskan. Baris diterima akan masuk sebagai <strong>belum ditinjau</strong>; dataset tetap atau kembali menjadi draft.</p><dl><div><dt>Diterima</dt><dd>{{ number_format($counts['accepted'] ?? 0) }}</dd></div><div><dt>Ditolak</dt><dd>{{ number_format($counts['rejected'] ?? 0) }}</dd></div><div><dt>Belum</dt><dd>{{ number_format($counts['proposed'] ?? 0) }}</dd></div></dl><form method="POST" action="{{ route('admin.ai-extractions.approve', $job) }}" data-confirm="Masukkan semua kandidat yang diterima ke observasi draft? Setelah ini data tetap wajib melewati kontrol kualitas.">@csrf<button class="button button--success button--block" type="submit" @disabled(($counts['proposed'] ?? 0) > 0 || ($counts['accepted'] ?? 0) < 1)>Finalisasi ke dataset draft</button></form></section>
        <section class="panel reject-panel"><span class="panel-kicker">Tolak ekstraksi</span><h2>Tutup tanpa menerapkan</h2><p>Gunakan bila dokumen keliru, tabel tidak relevan, atau hasil ekstraksi tidak dapat dipercaya.</p><form class="form-stack" method="POST" action="{{ route('admin.ai-extractions.reject', $job) }}" data-confirm="Tolak seluruh ekstraksi? Tidak ada observasi yang akan dibuat.">@csrf<div class="field"><label for="reason">Alasan <span>(opsional)</span></label><textarea id="reason" name="reason" rows="4" maxlength="1000"></textarea></div><button class="button button--danger button--block" type="submit">Tolak seluruh ekstraksi</button></form></section>
    </div>
    @elseif($job->status === 'approved')
        <section class="panel approved-next"><span class="panel-kicker">Gerbang berikutnya</span><h2>Ekstraksi sudah diterapkan.</h2><p>Observasi masih berstatus belum ditinjau. Periksa anomali dan bukti sumber sebelum publikasi.</p><a class="button button--ink" href="{{ route('admin.datasets.observations.index', $job->dataset) }}">Buka kontrol kualitas</a></section>
    @endif
@endif
@endsection
