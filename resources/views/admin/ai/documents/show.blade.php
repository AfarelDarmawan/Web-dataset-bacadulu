@extends('layouts.admin')

@section('title', 'Dokumen '.$sourceDocument->original_name)
@section('page_title', 'Dokumen sumber')

@section('content')
@php
    $activeJob = $jobs->first(fn ($job) => in_array($job->status, ['queued', 'processing', 'review'], true));
    $documentStatusLabels = ['uploaded' => 'Siap diproses', 'processing' => 'Sedang diproses', 'ready' => 'Siap direview', 'failed' => 'Gagal'];
    $jobStatusLabels = ['queued' => 'Dalam antrean', 'processing' => 'Sedang diproses', 'review' => 'Perlu review', 'approved' => 'Diterapkan', 'rejected' => 'Ditolak', 'failed' => 'Gagal'];
@endphp
<nav class="backline"><a href="{{ route('admin.ai.index') }}">← Ekstraksi dokumen</a></nav>
<div class="dataset-admin-head source-document-head">
    <div><span class="eyebrow">{{ strtoupper($sourceDocument->extension) }} / {{ $sourceDocument->dataset->code }}</span><h1>{{ $sourceDocument->original_name }}</h1><p>{{ $sourceDocument->dataset->title }} · diunggah {{ $sourceDocument->created_at->format('d M Y, H:i') }}</p></div>
    <span class="status status--large status--{{ $sourceDocument->status }}">{{ $documentStatusLabels[$sourceDocument->status] ?? ucfirst($sourceDocument->status) }}</span>
</div>

<div class="detail-grid">
    <section class="panel">
        <div class="panel__head"><div><span class="panel-kicker">Integritas sumber</span><h2>Identitas dokumen</h2></div><a href="{{ route('admin.source-documents.download', $sourceDocument) }}">Unduh sumber →</a></div>
        <dl class="definition-list">
            <div><dt>Dataset tujuan</dt><dd><a class="text-link" href="{{ route('admin.datasets.edit', $sourceDocument->dataset) }}">{{ $sourceDocument->dataset->title }}</a></dd></div>
            <div><dt>Penyedia</dt><dd>{{ $sourceDocument->dataset->provider->name }}</dd></div>
            <div><dt>Ukuran</dt><dd>{{ number_format($sourceDocument->size_bytes / 1024, 1) }} KB</dd></div>
            <div><dt>MIME</dt><dd><code>{{ $sourceDocument->mime_type }}</code></dd></div>
            <div><dt>SHA-256</dt><dd class="hash-value"><code>{{ $sourceDocument->sha256 }}</code></dd></div>
            <div><dt>Uploader</dt><dd>{{ $sourceDocument->uploader?->name ?? 'Akun telah dihapus' }}</dd></div>
        </dl>
    </section>

    <aside class="panel extraction-launch">
        <span class="panel-kicker">02 / Ekstraksi</span>
        @if($activeJob?->status === 'review')
            <h2>Hasil menunggu review</h2>
            <p>Selesaikan keputusan kandidat pada job #{{ $activeJob->id }} sebelum membuat ekstraksi baru.</p>
            <a class="button button--ink button--block" href="{{ route('admin.ai-extractions.show', $activeJob) }}">Buka hasil ekstraksi</a>
        @elseif($activeJob)
            <h2>Proses masih tercatat berjalan</h2>
            <p>Job #{{ $activeJob->id }} dimulai {{ $activeJob->started_at?->diffForHumans() ?? $activeJob->created_at->diffForHumans() }}. Jangan mengirim permintaan kedua saat proses valid masih berjalan.</p>
            @if($activeJob->isStale())
                <div class="config-warning"><strong>Proses terdeteksi macet</strong><p>Respons melewati batas aman. Pulihkan statusnya sebelum mencoba ulang.</p></div>
                <form method="POST" action="{{ route('admin.ai-extractions.recover', $activeJob) }}" data-confirm="Tandai proses ini gagal agar dokumen dapat diekstrak ulang?">@csrf<button class="button button--danger button--block" type="submit">Pulihkan proses macet</button></form>
            @else
                <div class="processing-note"><span class="processing-note__dot"></span><div><strong>Ekstraksi berlangsung</strong><small>Muat ulang halaman untuk melihat status terbaru.</small></div></div>
            @endif
        @else
            <h2>{{ in_array($sourceDocument->extension, ['csv', 'txt'], true) ? 'Buat staging lokal' : 'Jalankan ekstraksi AI' }}</h2>
            @if(in_array($sourceDocument->extension, ['csv', 'txt'], true))
                <p>CSV dibaca di server dan tidak dikirim ke penyedia AI. Hasil tetap wajib direview.</p>
                <form method="POST" action="{{ route('admin.source-documents.extract', $sourceDocument) }}" data-busy-form data-busy-label="Membaca CSV…">@csrf<button class="button button--ink button--block" type="submit">Baca CSV ke staging</button></form>
            @else
                <p>Dokumen akan dikirim ke OpenAI hanya untuk ekstraksi terstruktur. Respons disimpan sebagai staging, bukan data publik.</p>
                @if(config('bacadulu.ai.enabled') && config('bacadulu.ai.api_key'))
                    <form class="form-stack" method="POST" action="{{ route('admin.source-documents.extract', $sourceDocument) }}" data-busy-form data-busy-label="Mengekstrak dokumen…">@csrf<label class="check-control"><input type="checkbox" name="confirm_external_processing" value="1" required><span>Saya memahami dokumen ini akan diproses oleh layanan OpenAI dan sudah memiliki kewenangan atas datanya.</span></label><button class="button button--ink button--block" type="submit">Ekstrak ke staging</button></form>
                @else
                    <div class="config-warning"><strong>OpenAI belum aktif</strong><p>Tambahkan konfigurasi pada <code>.env</code>. Dokumen tetap aman di private storage dan belum dikirim ke luar.</p></div>
                @endif
            @endif
        @endif
        <small class="launch-footnote">Tidak ada aksi di halaman ini yang dapat memublikasikan dataset.</small>
    </aside>
</div>

<section class="panel panel--flush">
    <div class="panel__head panel__head--padded"><div><span class="panel-kicker">Riwayat ekstraksi</span><h2>Riwayat proses</h2></div></div>
    @if($jobs->isEmpty())
        <div class="panel-empty panel-empty--borderless"><span>Belum diproses</span><p>Jalankan ekstraksi untuk membuat kandidat observasi.</p></div>
    @else
        <div class="table-scroll"><table class="data-table admin-table"><thead><tr><th>Job</th><th>Metode</th><th>Waktu</th><th>Kandidat</th><th>Review</th><th>Status</th><th></th></tr></thead><tbody>@foreach($jobs as $job)<tr><td><strong>#{{ $job->id }}</strong><small>{{ $job->prompt_version }}</small></td><td>{{ $job->extractor === 'local_csv' ? 'CSV lokal' : $job->model }}</td><td>{{ $job->created_at->format('d M Y, H:i') }}<small>{{ $job->completed_at?->diffForHumans() ?? 'Sedang berjalan' }}</small></td><td>{{ number_format($job->rows_count) }}<small>{{ number_format($job->invalid_rows_count) }} tidak valid</small></td><td>{{ number_format($job->accepted_rows_count) }} diterima<small>{{ number_format($job->proposed_rows_count) }} belum diputuskan</small></td><td><span class="status status--{{ $job->status }}">{{ $jobStatusLabels[$job->status] ?? ucfirst($job->status) }}</span></td><td><a class="table-action" href="{{ route('admin.ai-extractions.show', $job) }}">Buka →</a></td></tr>@endforeach</tbody></table></div>
    @endif
</section>

@php($sourceLocked = $jobs->contains(fn ($job) => in_array($job->status, ['queued', 'processing', 'review', 'approved'], true)))
@unless($sourceLocked)
<section class="panel danger-zone source-delete"><span class="panel-kicker">Retensi sumber</span><p>Hapus hanya bila dokumen salah unggah. Seluruh staging dan job yang terkait ikut terhapus.</p><form method="POST" action="{{ route('admin.source-documents.destroy', $sourceDocument) }}" data-confirm="Hapus dokumen sumber dan seluruh hasil ekstraksi yang belum disetujui?">@csrf @method('DELETE')<button type="submit">Hapus dokumen</button></form></section>
@else
<section class="panel source-delete"><span class="panel-kicker">Retensi sumber</span><p>Dokumen dikunci selama ada review aktif atau setelah observasi diterapkan. Tolak job review bila dokumen memang salah.</p></section>
@endunless
@endsection
