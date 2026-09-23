@extends('layouts.admin')
@section('title', 'Sinkronisasi #'.$run->id)
@section('page_title', 'Review sinkronisasi')
@section('content')
@php($runLabel = ['queued'=>'Dalam antrean','processing'=>'Sedang mengambil','review'=>'Perlu review','applied'=>'Diterapkan','rejected'=>'Ditolak','failed'=>'Gagal'][$run->status] ?? ucfirst($run->status))
<nav class="backline"><a href="{{ route('admin.automation.index', ['dataset' => $connector->dataset_id]) }}">← Sinkronisasi BPS koleksi ini</a></nav>
<div class="dataset-admin-head extraction-job-head">
    <div><span class="eyebrow">Sinkronisasi #{{ $run->id }} / BPS</span><h1>{{ $connector->name }}</h1><p>{{ $connector->dataset->title }} · {{ $connector->variable->name }}</p></div>
    <span class="status status--large status--{{ $run->status }}">{{ $runLabel }}</span>
</div>

<div class="extraction-summary">
    <section class="panel extraction-brief"><span class="panel-kicker">Sumber resmi</span><h2>{{ data_get($run->metadata, 'source_schema.name', $connector->variable->name) }}</h2><p>{{ data_get($run->metadata, 'source_schema.definition') ?: 'Definisi tidak dikirim pada respons ini.' }}</p><small>Domain {{ data_get($run->metadata, 'domain') }} · variabel {{ data_get($run->metadata, 'variable_id') }} · satuan {{ data_get($run->metadata, 'source_schema.unit') ?: '—' }}</small></section>
    <dl class="extraction-facts">
        <div><dt>Total staging</dt><dd>{{ number_format($run->rows_count) }}</dd></div>
        <div><dt>Baru</dt><dd>{{ number_format($run->created_rows_count) }}</dd></div>
        <div><dt>Berubah</dt><dd>{{ number_format($run->updated_rows_count) }}</dd></div>
        <div><dt>Invalid</dt><dd>{{ number_format($run->invalid_rows_count) }}</dd></div>
    </dl>
</div>

@if(data_get($run->metadata, 'mock'))<div class="config-warning config-warning--mock"><strong>Data simulasi</strong><p>Baris ini dibuat oleh mode mock dan bukan angka resmi BPS. Gunakan hanya untuk menguji alur.</p></div>@endif
@if(data_get($run->metadata, 'schema_changed'))<div class="config-warning"><strong>Metadata sumber berubah</strong><p>Nama, satuan, definisi, atau dimensi BPS berbeda dari sinkronisasi sebelumnya. Periksa seluruh warning sebelum menerima.</p></div>@endif

@if($run->status === 'failed')
    <section class="panel failure-panel"><span class="panel-kicker">{{ $run->error_code }}</span><h2>Sinkronisasi tidak selesai</h2><p>{{ $run->error_message }}</p><a class="button button--line" href="{{ route('admin.automation.connectors.edit', $connector) }}">Periksa konfigurasi</a></section>
@elseif(in_array($run->status, ['queued', 'processing'], true))
    <section class="panel processing-panel"><div class="processing-note"><span class="processing-note__dot"></span><div><strong>{{ $runLabel }}</strong><small>Proses dimulai {{ $run->started_at?->diffForHumans() ?? $run->created_at->diffForHumans() }}.</small></div></div><p>Jangan menjalankan konektor yang sama dua kali. Muat ulang halaman untuk memeriksa status.</p></section>
@else
    <form class="toolbar" method="GET" action="{{ route('admin.automation.runs.show', [$connector, $run]) }}">
        <div class="field"><label for="action">Perubahan</label><select id="action" name="action"><option value="">Semua</option>@foreach(['create'=>'Baru','update'=>'Berubah','unchanged'=>'Tidak berubah'] as $value=>$label)<option value="{{ $value }}" @selected(request('action') === $value)>{{ $label }}</option>@endforeach</select></div>
        <div class="field"><label for="validation">Validasi</label><select id="validation" name="validation"><option value="">Semua</option>@foreach(['valid'=>'Valid','warning'=>'Peringatan','invalid'=>'Invalid'] as $value=>$label)<option value="{{ $value }}" @selected(request('validation') === $value)>{{ $label }}</option>@endforeach</select></div>
        <div class="field"><label for="status">Keputusan</label><select id="status" name="status"><option value="">Semua</option>@foreach(['proposed'=>'Belum','accepted'=>'Diterima','rejected'=>'Ditolak','applied'=>'Diterapkan'] as $value=>$label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></div>
        <button class="button button--ink button--small" type="submit">Filter</button><a href="{{ route('admin.automation.runs.show', [$connector, $run]) }}">Reset</a>
    </form>

    @if($run->isReviewable())
    <form id="bulk-review" method="POST" action="{{ route('admin.automation.runs.bulk', [$connector, $run]) }}">@csrf
        <div class="review-commandbar"><div><strong>Keputusan massal</strong><span>Baris invalid tidak bisa diterima. Perubahan nilai ditandai warning agar diperiksa.</span></div><div><button name="action" value="accept_selected" type="submit">Terima pilihan</button><button name="action" value="reject_selected" type="submit">Tolak pilihan</button><button class="command-positive" name="action" value="accept_all_valid" type="submit">Terima valid & warning</button><button class="command-negative" name="action" value="reject_all_invalid" type="submit">Tolak semua invalid</button></div></div>
    @endif

    <section class="panel panel--flush extraction-table-panel">
        @if($rows->isEmpty())<div class="panel-empty panel-empty--borderless"><span>TIDAK ADA BARIS</span><p>Ubah filter untuk melihat staging lainnya.</p></div>@else
        <div class="table-scroll"><table class="data-table admin-table extraction-table sync-table"><thead><tr>@if($run->isReviewable())<th><label class="table-check"><input type="checkbox" data-review-check-all aria-label="Pilih semua pada halaman ini"></label></th>@endif<th>#</th><th>Wilayah</th><th>Periode</th><th>Nilai BPS</th><th>Perubahan</th><th>Validasi</th><th>Keputusan</th></tr></thead><tbody>@foreach($rows as $row)<tr class="extraction-row extraction-row--{{ $row->validation_status }}">@if($run->isReviewable())<td>@if($row->status === 'proposed')<label class="table-check"><input type="checkbox" name="row_ids[]" value="{{ $row->id }}" data-review-checkbox aria-label="Pilih baris {{ $row->row_index }}"></label>@endif</td>@endif<td><code>{{ $row->row_index }}</code></td><td><strong>{{ $row->geography_name }}</strong><small>{{ $row->geography_code }}</small></td><td>{{ $row->period }}</td><td><strong>{{ $row->value_numeric !== null ? rtrim(rtrim(number_format((float) $row->value_numeric, 6, '.', ','), '0'), '.') : $row->value_text }}</strong><small>{{ $row->source_reference }}</small></td><td><span class="change-chip change-chip--{{ $row->proposed_action }}">{{ ['create'=>'Baru','update'=>'Berubah','unchanged'=>'Tetap'][$row->proposed_action] ?? $row->proposed_action }}</span></td><td><span class="mini-status mini-status--{{ $row->validation_status }}">{{ $row->validation_status }}</span>@if($row->validation_issues)<small class="issue-copy">{{ implode(' ', $row->validation_issues) }}</small>@endif</td><td><span class="status status--{{ $row->status }}">{{ $row->status === 'proposed' ? 'Belum' : ucfirst($row->status) }}</span></td></tr>@endforeach</tbody></table></div>
        <div class="pagination-wrap pagination-wrap--panel">{{ $rows->links() }}</div>@endif
    </section>
    @if($run->isReviewable())</form>@endif

    @if($run->isReviewable())
    <div class="finalize-grid">
        <section class="panel finalize-panel"><span class="panel-kicker">Terapkan ke draft</span><h2>Masukkan hasil yang diterima</h2><p>Observasi baru atau berubah masuk sebagai <strong>unreviewed</strong>. Dataset terbit akan kembali menjadi draft sampai QC selesai.</p><dl><div><dt>Diterima</dt><dd>{{ number_format($counts['accepted'] ?? 0) }}</dd></div><div><dt>Ditolak</dt><dd>{{ number_format($counts['rejected'] ?? 0) }}</dd></div><div><dt>Belum</dt><dd>{{ number_format($counts['proposed'] ?? 0) }}</dd></div></dl><form method="POST" action="{{ route('admin.automation.runs.apply', [$connector, $run]) }}" data-confirm="Terapkan baris yang diterima ke observasi draft?">@csrf<button class="button button--success button--block" type="submit" @disabled(($counts['proposed'] ?? 0) > 0 || ($counts['accepted'] ?? 0) < 1)>Terapkan ke observasi</button></form></section>
        <section class="panel reject-panel"><span class="panel-kicker">Batalkan staging</span><h2>Tutup tanpa menerapkan</h2><p>Gunakan bila ID variabel, periode, satuan, atau wilayah salah. Data observasi tidak akan berubah.</p><form method="POST" action="{{ route('admin.automation.runs.reject', [$connector, $run]) }}" data-confirm="Tutup sinkronisasi ini tanpa menerapkan data?">@csrf<button class="button button--danger button--block" type="submit">Tolak seluruh sinkronisasi</button></form></section>
    </div>
    @elseif($run->status === 'applied')
        <section class="panel approved-next"><span class="panel-kicker">Tahap berikutnya</span><h2>Staging sudah diterapkan.</h2><p>Periksa observasi baru dan perubahan nilai pada quality control sebelum menerbitkan kembali dataset.</p><a class="button button--ink" href="{{ route('admin.datasets.observations.index', $connector->dataset) }}">Buka quality control</a></section>
    @endif
@endif
@endsection