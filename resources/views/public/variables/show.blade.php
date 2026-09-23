@extends('layouts.public')

@section('title', $variable->name.' — BacaDulu Dataset')
@section('meta_description', $variable->definition ?: $dataset->summary)

@section('content')
<section class="variable-detail-hero">
    <div class="container-wide">
        <nav class="breadcrumb" aria-label="Breadcrumb"><a href="{{ route('datasets.index') }}">Katalog variabel</a><span>/</span><a href="{{ route('datasets.show', $dataset->slug) }}">{{ $dataset->code }}</a><span>/</span><span>{{ $variable->code }}</span></nav>
        <div class="variable-detail-hero__grid">
            <div>
                <div class="variable-detail-hero__meta"><span class="scope-badge scope-badge--{{ $dataset->data_scope }}">{{ $dataset->scopeLabel() }}</span><code>{{ $variable->code }}</code></div>
                <h1>{{ $variable->name }}</h1>
                <p>{{ $variable->definition ?: 'Definisi rinci belum dicantumkan. Periksa konteks dataset dan metodologi sumber sebelum menggunakan variabel ini.' }}</p>
            </div>
            <aside class="variable-source-card">
                <span>Sumber variabel</span>
                <strong>{{ $dataset->provider->name }}</strong>
                <a href="{{ route('datasets.show', $dataset->slug) }}">{{ $dataset->title }} →</a>
            </aside>
        </div>
        <dl class="variable-detail-facts">
            <div><dt>Satuan</dt><dd>{{ $variable->unit ?: 'Tanpa satuan' }}</dd></div>
            <div><dt>Tipe data</dt><dd>{{ ['numeric'=>'Numerik','text'=>'Teks','percentage'=>'Persentase','currency'=>'Mata uang','index'=>'Indeks'][$variable->data_type] ?? ucfirst($variable->data_type) }}</dd></div>
            <div><dt>{{ $dataset->isCorporate() ? 'Perusahaan' : 'Wilayah' }}</dt><dd>{{ number_format($entityCount) }}</dd></div>
            <div><dt>Periode</dt><dd>{{ $periods->isEmpty() ? 'Belum tersedia' : $periods->last().($periods->count() > 1 ? '–'.$periods->first() : '') }}</dd></div>
            <div><dt>Observasi</dt><dd>{{ number_format($observationCount) }}</dd></div>
            <div><dt>Akses</dt><dd>{{ ['open'=>'Terbuka','restricted'=>'Terbatas','commercial'=>'Berlisensi'][$dataset->access_type] ?? ucfirst($dataset->access_type) }}</dd></div>
        </dl>
    </div>
</section>

<section class="section variable-detail">
    <div class="container-wide variable-detail__layout">
        <main>
            <section class="detail-section detail-section--stack">
                <div class="detail-heading"><div><span class="eyebrow">Pratinjau data</span><h2>{{ $dataset->entityLabel() }} dan periode</h2><p>{{ $dataset->isOpen() ? 'Nilai ditampilkan untuk membantu pemeriksaan awal.' : 'Struktur tersedia, tetapi nilai dilindungi sampai akses disetujui.' }}</p></div></div>
                <form class="preview-filter" method="GET">
                    <select name="entity" aria-label="Filter {{ $dataset->entityLabelLower() }}"><option value="">Semua {{ $dataset->entityLabelLower() }}</option>@foreach($entities as $entity)<option value="{{ $entity->geography_code }}" @selected(request('entity') === $entity->geography_code)>{{ $entity->geography_name }}</option>@endforeach</select>
                    <select name="period" aria-label="Filter periode"><option value="">Semua periode</option>@foreach($periods as $period)<option value="{{ $period }}" @selected(request('period') === $period)>{{ $period }}</option>@endforeach</select>
                    <button type="submit">Tampilkan</button>
                </form>
                @if($observations->isEmpty())
                    <div class="inline-empty">Tidak ada observasi untuk kombinasi filter ini.</div>
                @else
                    <div class="table-scroll"><table class="data-table data-table--preview"><thead><tr><th>{{ $dataset->entityLabel() }}</th><th>Periode</th><th>Nilai</th><th>Kualitas</th></tr></thead><tbody>@foreach($observations as $observation)<tr><td>{{ $observation->geography_name }}<small>{{ $observation->geography_code }}</small></td><td>{{ $observation->period }}</td><td>@if($dataset->isOpen())<strong>{{ $observation->displayValue() }}</strong> <small>{{ $variable->unit }}</small>@else<span class="protected-value">Dilindungi</span>@endif</td><td><span class="mini-status mini-status--{{ $observation->quality_status }}">{{ ucfirst($observation->quality_status) }}</span></td></tr>@endforeach</tbody></table></div>
                @endif
            </section>

            <section class="detail-section detail-section--stack">
                <div class="detail-heading"><div><span class="eyebrow">Konteks penggunaan</span><h2>Dataset dan metodologi</h2></div></div>
                <div class="prose">{!! nl2br(e($dataset->methodology ?: $dataset->description ?: $dataset->summary)) !!}</div>
                @if($dataset->source_url)<a class="source-link" href="{{ $dataset->source_url }}" target="_blank" rel="noopener noreferrer">Buka sumber resmi ↗</a>@endif
            </section>
        </main>

        <aside class="variable-action-panel">
            <div class="variable-action-panel__sticky">
                <span class="eyebrow">Gunakan variabel</span>
                <h2>{{ $dataset->isOpen() ? 'Siapkan ekspor CSV' : 'Masukkan ke permintaan akses' }}</h2>
                <p>Pilihan variabel ini akan otomatis ditandai pada halaman dataset. Di sana kamu dapat memilih {{ $dataset->entityLabelLower() }} dan periode.</p>
                <a class="button button--ink button--block" href="{{ route('datasets.show', ['dataset' => $dataset->slug, 'variable' => $variable->id]).'#access' }}">Pilih variabel ini</a>
                <dl><div><dt>Dataset</dt><dd>{{ $dataset->code }}</dd></div><div><dt>Lisensi</dt><dd>{{ $dataset->license ?: 'Kebijakan penyedia' }}</dd></div></dl>
                @if($relatedVariables->isNotEmpty())
                    <div class="related-variables"><strong>Variabel terkait</strong>@foreach($relatedVariables as $related)<a href="{{ route('datasets.variables.show', [$dataset->slug, $related]) }}"><span>{{ $related->name }}</span><code>{{ $related->code }}</code></a>@endforeach</div>
                @endif
            </div>
        </aside>
    </div>
</section>
@endsection
