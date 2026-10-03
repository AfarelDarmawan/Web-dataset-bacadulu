@extends('layouts.public')

@section('title', $variable->name.' — BacaDulu Dataset')
@section('meta_description', $variable->definition ?: $dataset->summary)

@push('page_styles')
    <link rel="stylesheet" href="{{ asset('assets/bacadulu-detail.css') }}?v=2.1.1">
@endpush

@push('page_scripts')
    <script src="{{ asset('assets/bacadulu-detail.js') }}?v=2.1.0" defer></script>
@endpush

@php
    $accessNames = [
        'open' => 'Terbuka',
        'restricted' => 'Terbatas',
        'commercial' => 'Berlisensi',
    ];
    $typeNames = [
        'numeric' => 'Numerik',
        'text' => 'Teks',
        'percentage' => 'Persentase',
        'currency' => 'Mata uang',
        'index' => 'Indeks',
    ];
    $periodLabel = $periods->isEmpty()
        ? 'Belum tersedia'
        : $periods->last().($periods->count() > 1 ? '–'.$periods->first() : '');
@endphp

@section('content')
<div class="dataset-show-v2 variable-show-v2" data-dataset-detail>
    <section class="dataset-show-hero">
        <span class="dataset-show-hero__orb dataset-show-hero__orb--one" aria-hidden="true"></span>
        <span class="dataset-show-hero__orb dataset-show-hero__orb--two" aria-hidden="true"></span>

        <div class="container-wide">
            <nav class="dataset-show-breadcrumb" aria-label="Breadcrumb" data-hero-item>
                <a href="{{ route('datasets.index') }}">
                    <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m12.5 5-5 5 5 5"></path></svg>
                    Katalog variabel
                </a>
                <span>/</span>
                <a href="{{ route('datasets.show', $dataset->slug) }}">{{ $dataset->code }}</a>
                <span>/</span>
                <span>{{ $variable->code }}</span>
            </nav>

            <div class="dataset-show-hero__grid">
                <div class="dataset-show-hero__copy">
                    <div class="dataset-show-badges" data-hero-item>
                        <span class="dataset-show-badge dataset-show-badge--category">{{ $dataset->category }}</span>
                        <span class="dataset-show-badge dataset-show-badge--scope">{{ $dataset->scopeLabel() }}</span>
                        <span class="dataset-show-badge dataset-show-badge--{{ $dataset->access_type }}">{{ $accessNames[$dataset->access_type] ?? ucfirst($dataset->access_type) }}</span>
                    </div>

                    <p class="dataset-show-kicker" data-hero-item><i aria-hidden="true"></i>Detail variabel · {{ $variable->code }}</p>
                    <h1 data-hero-item>{{ $variable->name }}</h1>
                    <p class="dataset-show-hero__lead" data-hero-item>{{ $variable->definition ?: 'Definisi rinci belum dicantumkan. Periksa konteks dataset dan metodologi sumber sebelum menggunakan variabel ini.' }}</p>

                    <a class="dataset-show-provider variable-show-dataset-link" href="{{ route('datasets.show', $dataset->slug) }}" data-hero-item>
                        <span class="dataset-show-provider__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none"><path d="M5 4h14v16H5V4Z"></path><path d="M8 8h8M8 12h8M8 16h5"></path></svg>
                        </span>
                        <span>
                            <small>Berasal dari dataset</small>
                            <strong>{{ $dataset->title }}</strong>
                        </span>
                        <svg class="variable-show-dataset-link__arrow" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4 10h12M11 5l5 5-5 5"></path></svg>
                    </a>
                </div>

                <div class="dataset-show-scene" data-detail-scene aria-hidden="true">
                    <div class="dataset-show-scene__bar">
                        <span>VARIABLE EXPLORER</span>
                        <b><i></i> SIAP DIPERIKSA</b>
                    </div>

                    <div class="dataset-show-scene__canvas">
                        <svg class="dataset-show-scene__paths" viewBox="0 0 500 330" preserveAspectRatio="none">
                            <path data-detail-line d="M80 77 C165 77 165 162 245 165"></path>
                            <path data-detail-line d="M80 252 C165 252 165 174 245 169"></path>
                            <path data-detail-line d="M277 167 C350 167 356 98 427 98"></path>
                            <path data-detail-line d="M277 169 C350 169 356 236 427 236"></path>
                        </svg>

                        <div class="dataset-show-scene__node dataset-show-scene__node--source-one" data-detail-node>
                            <span><svg viewBox="0 0 24 24" fill="none"><path d="M5 4h14v16H5V4ZM8 8h8M8 12h8M8 16h5"></path></svg></span>
                            <div><small>DATASET</small><strong>{{ $dataset->code }}</strong></div>
                        </div>

                        <div class="dataset-show-scene__node dataset-show-scene__node--source-two" data-detail-node>
                            <span><svg viewBox="0 0 24 24" fill="none"><path d="M4 19V9M10 19V5M16 19v-7M22 19H2"></path></svg></span>
                            <div><small>FORMAT</small><strong>{{ $variable->unit ?: 'Tanpa satuan' }}</strong></div>
                        </div>

                        <div class="dataset-show-scene__core variable-show-scene__core" data-detail-core>
                            <i></i><i></i>
                            <svg viewBox="0 0 48 48" fill="none"><path d="M14 10h20v28H14V10Z"></path><path d="M19 18h10M19 24h10M19 30h7"></path></svg>
                            <strong>VAR</strong>
                        </div>

                        <div class="dataset-show-scene__output dataset-show-scene__output--one" data-detail-node>
                            <small>{{ $dataset->isCorporate() ? 'PERUSAHAAN' : 'WILAYAH' }}</small>
                            <strong>{{ number_format($entityCount) }}</strong>
                            <span></span>
                        </div>

                        <div class="dataset-show-scene__output dataset-show-scene__output--two" data-detail-node>
                            <small>OBSERVASI</small>
                            <strong>{{ number_format($observationCount) }}</strong>
                            <span></span>
                        </div>

                        <span class="dataset-show-scene__pulse dataset-show-scene__pulse--one" data-detail-pulse></span>
                        <span class="dataset-show-scene__pulse dataset-show-scene__pulse--two" data-detail-pulse></span>
                    </div>

                    <div class="dataset-show-scene__footer">
                        <span>Dataset</span><i></i><span>Variabel</span><i></i><span>Observasi</span>
                    </div>
                </div>
            </div>

            <dl class="dataset-show-facts" data-hero-item>
                <div><dt>Satuan</dt><dd>{{ $variable->unit ?: 'Tanpa satuan' }}</dd></div>
                <div><dt>Tipe data</dt><dd>{{ $typeNames[$variable->data_type] ?? ucfirst($variable->data_type) }}</dd></div>
                <div><dt>{{ $dataset->isCorporate() ? 'Perusahaan' : 'Wilayah' }}</dt><dd>{{ number_format($entityCount) }} <small>entitas</small></dd></div>
                <div><dt>Periode</dt><dd>{{ $periodLabel }}</dd></div>
            </dl>
        </div>
    </section>

    <section class="dataset-show-content">
        <div class="container-wide dataset-show-layout">
            <main class="dataset-show-main">
                <section class="dataset-show-card" id="preview" data-detail-reveal>
                    <header class="dataset-show-section-head">
                        <span class="dataset-show-section-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none"><path d="M4 19V9M10 19V5M16 19v-7M22 19H2"></path></svg>
                        </span>
                        <div>
                            <small>PRATINJAU DATA</small>
                            <h2>{{ $dataset->entityLabel() }} dan periode</h2>
                            <p>{{ $dataset->isOpen() ? 'Gunakan filter untuk memeriksa nilai dan cakupan variabel.' : 'Struktur tersedia, tetapi nilai dilindungi sampai akses disetujui.' }}</p>
                        </div>
                    </header>

                    <form class="dataset-show-filter variable-show-filter" method="GET" action="{{ route('datasets.variables.show', [$dataset->slug, $variable]) }}#preview">
                        <label>
                            <span>{{ $dataset->entityLabel() }}</span>
                            <select name="entity">
                                <option value="">Semua {{ $dataset->entityLabelLower() }}</option>
                                @foreach($entities as $entity)
                                    <option value="{{ $entity->geography_code }}" @selected(request('entity') === $entity->geography_code)>{{ $entity->geography_name }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label>
                            <span>Periode</span>
                            <select name="period">
                                <option value="">Semua periode</option>
                                @foreach($periods as $period)
                                    <option value="{{ $period }}" @selected(request('period') === $period)>{{ $period }}</option>
                                @endforeach
                            </select>
                        </label>

                        <button type="submit">
                            Tampilkan
                            <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4 10h12M11 5l5 5-5 5"></path></svg>
                        </button>
                    </form>

                    @if($observations->isEmpty())
                        <div class="dataset-show-empty">Tidak ada observasi untuk kombinasi filter ini.</div>
                    @else
                        <div class="dataset-show-table-wrap">
                            <table class="dataset-show-table variable-show-table">
                                <thead>
                                    <tr>
                                        <th>{{ $dataset->entityLabel() }}</th>
                                        <th>Periode</th>
                                        <th>Nilai</th>
                                        <th>Kualitas</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($observations as $observation)
                                        <tr>
                                            <td data-label="{{ $dataset->entityLabel() }}">
                                                <strong>{{ $observation->geography_name }}</strong>
                                                <small>{{ $observation->geography_code }}</small>
                                            </td>
                                            <td data-label="Periode">{{ $observation->period }}</td>
                                            <td data-label="Nilai">
                                                @if($dataset->isOpen())
                                                    <strong>{{ $observation->displayValue() }}</strong>
                                                    <small>{{ $variable->unit }}</small>
                                                @else
                                                    <span class="dataset-show-protected">Dilindungi</span>
                                                @endif
                                            </td>
                                            <td data-label="Kualitas">
                                                <span class="dataset-show-status dataset-show-status--{{ $observation->quality_status }}">{{ ucfirst($observation->quality_status) }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </section>

                <section class="dataset-show-card" data-detail-reveal>
                    <header class="dataset-show-section-head">
                        <span class="dataset-show-section-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none"><path d="M5 3h11l3 3v15H5V3Z"></path><path d="M9 10h6M9 14h6M9 18h4M15 3v4h4"></path></svg>
                        </span>
                        <div>
                            <small>KONTEKS PENGGUNAAN</small>
                            <h2>Dataset dan metodologi</h2>
                        </div>
                    </header>

                    <div class="dataset-show-prose">{!! nl2br(e($dataset->methodology ?: $dataset->description ?: $dataset->summary)) !!}</div>

                    <div class="dataset-show-meta-strip variable-show-meta-strip">
                        <span><small>Dataset</small><strong>{{ $dataset->code }}</strong></span>
                        <span><small>Penyedia</small><strong>{{ $dataset->provider->name }}</strong></span>
                        <span><small>Lisensi</small><strong>{{ $dataset->license ?: 'Kebijakan penyedia' }}</strong></span>
                    </div>

                    @if($dataset->source_url)
                        <a class="dataset-show-source-link" href="{{ $dataset->source_url }}" target="_blank" rel="noopener noreferrer">Buka sumber resmi <span>↗</span></a>
                    @endif
                </section>
            </main>

            <aside class="dataset-show-access variable-show-action" data-detail-reveal>
                <div class="dataset-show-access__sticky">
                    <div class="dataset-show-access__head">
                        <span class="dataset-show-access__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none"><path d="M12 3v12M7 10l5 5 5-5M5 20h14"></path></svg>
                        </span>
                        <div>
                            <small>GUNAKAN VARIABEL</small>
                            <h2>{{ $dataset->isOpen() ? 'Siapkan ekspor CSV' : 'Ajukan akses data' }}</h2>
                        </div>
                    </div>

                    <p class="dataset-show-access__intro">Variabel ini akan otomatis dipilih. Selanjutnya kamu dapat menentukan {{ $dataset->entityLabelLower() }} dan periode pada halaman dataset.</p>

                    <div class="variable-show-next-step">
                        <span>ALUR SELANJUTNYA</span>
                        <ol>
                            <li class="is-current"><i>1</i><strong>Variabel dipilih</strong></li>
                            <li><i>2</i><strong>Atur cakupan</strong></li>
                            <li><i>3</i><strong>{{ $dataset->isOpen() ? 'Unduh data' : 'Kirim akses' }}</strong></li>
                        </ol>
                    </div>

                    <div class="variable-show-selected">
                        <span>VARIABEL TERPILIH</span>
                        <strong>{{ $variable->name }}</strong>
                        <code>{{ $variable->code }} · {{ $variable->unit ?: 'tanpa satuan' }}</code>
                    </div>

                    <div class="variable-show-action__button-wrap">
                        <a class="dataset-show-primary-button" href="{{ route('datasets.show', ['dataset' => $dataset->slug, 'variable' => $variable->id]).'#access' }}">
                            <span>Lanjut pilih cakupan</span>
                            <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4 10h12M11 5l5 5-5 5"></path></svg>
                        </a>
                    </div>

                    <dl class="variable-show-action__notes">
                        <div><dt>Akses</dt><dd>{{ $accessNames[$dataset->access_type] ?? ucfirst($dataset->access_type) }}</dd></div>
                        <div><dt>Dataset</dt><dd>{{ $dataset->code }}</dd></div>
                    </dl>

                    @if($relatedVariables->isNotEmpty())
                        <div class="variable-show-related">
                            <div class="variable-show-related__head"><span>Variabel terkait</span><small>{{ $relatedVariables->count() }} lainnya</small></div>
                            <div class="variable-show-related__list">
                                @foreach($relatedVariables as $related)
                                    <a href="{{ route('datasets.variables.show', [$dataset->slug, $related]) }}">
                                        <span><strong>{{ $related->name }}</strong><small>{{ $related->code }}</small></span>
                                        <i aria-hidden="true">→</i>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </aside>
        </div>
    </section>
</div>
@endsection
