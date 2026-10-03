@extends('layouts.public')

@section('title', $dataset->title.' — BacaDulu Dataset')
@section('meta_description', $dataset->summary)

@push('page_styles')
    <link rel="stylesheet" href="{{ asset('assets/bacadulu-detail.css') }}?v=2.0.0">
@endpush

@push('page_scripts')
    <script src="{{ asset('assets/bacadulu-detail.js') }}?v=2.0.0" defer></script>
@endpush

@php
    $whatsappNumber = (string) config('bacadulu.contact.whatsapp_number');
    $serviceHours = (string) config('bacadulu.contact.service_hours');
    $isCommercial = $dataset->access_type === 'commercial';
    $pricedVariables = $dataset->variables->filter(
        fn ($variable) => (float) $variable->price_per_cell > 0
    );
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
    $tierNames = [
        'open' => 'Terbuka',
        'standard' => 'Standar',
        'premium' => 'Premium',
    ];
    $periodLabel = $dataset->period_start && $dataset->period_end
        ? $dataset->period_start.'–'.$dataset->period_end
        : 'Belum ditentukan';
    $whatsappMessage = implode("\n", [
        'Halo Admin BacaDulu Dataset,',
        '',
        'Saya ingin menanyakan akses dataset:',
        $dataset->title.' ('.$dataset->code.')',
        '',
        'Kebutuhan saya cukup urgent untuk penelitian. Mohon dibantu informasi ketersediaan, estimasi biaya, dan waktu prosesnya.',
        '',
        'Terima kasih.',
    ]);
    $whatsappUrl = $whatsappNumber !== ''
        ? 'https://wa.me/'.$whatsappNumber.'?text='.rawurlencode($whatsappMessage)
        : null;
@endphp

@section('content')
<div class="dataset-show-v2" data-dataset-detail>
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
                <span>{{ $dataset->code }}</span>
            </nav>

            <div class="dataset-show-hero__grid">
                <div class="dataset-show-hero__copy">
                    <div class="dataset-show-badges" data-hero-item>
                        <span class="dataset-show-badge dataset-show-badge--category">{{ $dataset->category }}</span>
                        <span class="dataset-show-badge dataset-show-badge--scope">{{ $dataset->scopeLabel() }}</span>
                        <span class="dataset-show-badge dataset-show-badge--{{ $dataset->access_type }}">{{ $accessNames[$dataset->access_type] ?? ucfirst($dataset->access_type) }}</span>
                    </div>

                    <p class="dataset-show-kicker" data-hero-item><i aria-hidden="true"></i>Detail dataset · {{ $dataset->code }}</p>
                    <h1 data-hero-item>{{ $dataset->title }}</h1>
                    <p class="dataset-show-hero__lead" data-hero-item>{{ $dataset->summary }}</p>

                    <div class="dataset-show-provider" data-hero-item>
                        <span class="dataset-show-provider__icon" aria-hidden="true">
                            @if($dataset->isCorporate())
                                <svg viewBox="0 0 24 24" fill="none"><path d="M4 21V8l8-5 8 5v13M8 21v-6h8v6M8 10h1M15 10h1M2 21h20"></path></svg>
                            @else
                                <svg viewBox="0 0 24 24" fill="none"><path d="M4 20V7l4-3 4 3v13M12 10l4-3 4 3v10M2 20h20M7 9h2M7 13h2M15 12h2M15 16h2"></path></svg>
                            @endif
                        </span>
                        <span>
                            <small>Disediakan oleh</small>
                            <strong>{{ $dataset->provider->name }}</strong>
                        </span>
                    </div>
                </div>

                <div class="dataset-show-scene" data-detail-scene aria-hidden="true">
                    <div class="dataset-show-scene__bar">
                        <span>DATASET OVERVIEW</span>
                        <b><i></i> TERVERIFIKASI</b>
                    </div>
                    <div class="dataset-show-scene__canvas">
                        <svg class="dataset-show-scene__paths" viewBox="0 0 500 330" preserveAspectRatio="none">
                            <path data-detail-line d="M80 77 C165 77 165 162 245 165"></path>
                            <path data-detail-line d="M80 252 C165 252 165 174 245 169"></path>
                            <path data-detail-line d="M277 167 C350 167 356 98 427 98"></path>
                            <path data-detail-line d="M277 169 C350 169 356 236 427 236"></path>
                        </svg>

                        <div class="dataset-show-scene__node dataset-show-scene__node--source-one" data-detail-node>
                            <span><svg viewBox="0 0 24 24" fill="none"><path d="M4 19V8l8-5 8 5v11M8 19v-5h8v5M2 21h20"></path></svg></span>
                            <div><small>SUMBER</small><strong>{{ $dataset->isCorporate() ? 'Perusahaan' : 'Wilayah' }}</strong></div>
                        </div>
                        <div class="dataset-show-scene__node dataset-show-scene__node--source-two" data-detail-node>
                            <span><svg viewBox="0 0 24 24" fill="none"><path d="M5 19V9M12 19V5M19 19v-7M3 21h18"></path></svg></span>
                            <div><small>PERIODE</small><strong>{{ $periodLabel }}</strong></div>
                        </div>

                        <div class="dataset-show-scene__core" data-detail-core>
                            <i></i><i></i>
                            <svg viewBox="0 0 48 48" fill="none"><path d="M10 14c0-3.4 6.3-6 14-6s14 2.6 14 6-6.3 6-14 6-14-2.6-14-6Z"></path><path d="M10 14v10c0 3.4 6.3 6 14 6s14-2.6 14-6V14M10 24v10c0 3.4 6.3 6 14 6s14-2.6 14-6V24"></path></svg>
                            <strong>DATA</strong>
                        </div>

                        <div class="dataset-show-scene__output dataset-show-scene__output--one" data-detail-node>
                            <small>VARIABEL</small><strong>{{ number_format($dataset->variables_count) }}</strong><span></span>
                        </div>
                        <div class="dataset-show-scene__output dataset-show-scene__output--two" data-detail-node>
                            <small>OBSERVASI</small><strong>{{ number_format($dataset->observations_count) }}</strong><span></span>
                        </div>
                        <span class="dataset-show-scene__pulse dataset-show-scene__pulse--one" data-detail-pulse></span>
                        <span class="dataset-show-scene__pulse dataset-show-scene__pulse--two" data-detail-pulse></span>
                    </div>
                    <div class="dataset-show-scene__footer">
                        <span>Sumber</span><i></i><span>Validasi</span><i></i><span>Siap digunakan</span>
                    </div>
                </div>
            </div>

            <dl class="dataset-show-facts" data-hero-item>
                <div><dt>Periode</dt><dd>{{ $periodLabel }}</dd></div>
                <div><dt>Frekuensi</dt><dd>{{ $dataset->frequency ?: 'Belum ditentukan' }}</dd></div>
                <div><dt>Variabel</dt><dd>{{ number_format($dataset->variables_count) }} <small>kolom data</small></dd></div>
                <div><dt>Observasi</dt><dd>{{ number_format($dataset->observations_count) }} <small>baris data</small></dd></div>
            </dl>
        </div>
    </section>

    <section class="dataset-show-content">
        <div class="container-wide dataset-show-layout">
            <main class="dataset-show-main">
                <section class="dataset-show-card dataset-show-about" data-detail-reveal>
                    <header class="dataset-show-section-head">
                        <span class="dataset-show-section-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9"></circle><path d="M12 11v6M12 7.5h.01"></path></svg></span>
                        <div><small>TENTANG DATASET</small><h2>Pahami data sebelum digunakan</h2></div>
                    </header>
                    <div class="dataset-show-prose">{!! nl2br(e($dataset->description ?: $dataset->summary)) !!}</div>
                    <div class="dataset-show-meta-strip">
                        <span><small>Cakupan</small><strong>{{ $dataset->geographic_level ?: $dataset->entityLabel() }}</strong></span>
                        <span><small>Lisensi</small><strong>{{ $dataset->license ?: 'Kebijakan provider' }}</strong></span>
                        <span><small>Diperbarui</small><strong>{{ optional($dataset->last_updated_at)->format('d M Y') ?: 'Belum tersedia' }}</strong></span>
                    </div>
                </section>

                <section class="dataset-show-card" id="variables" data-detail-reveal>
                    <header class="dataset-show-section-head dataset-show-section-head--split">
                        <div class="dataset-show-section-title">
                            <span class="dataset-show-section-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M4 5h16M4 12h16M4 19h16M8 3v4M15 10v4M11 17v4"></path></svg></span>
                            <div><small>KAMUS VARIABEL</small><h2>Kolom yang tersedia</h2><p>Periksa definisi, satuan, dan akses setiap variabel.</p></div>
                        </div>
                        <span class="dataset-show-count">{{ $dataset->variables->count() }} variabel</span>
                    </header>

                    @if ($dataset->variables->isEmpty())
                        <div class="dataset-show-empty">Belum ada variabel yang dipublikasikan.</div>
                    @else
                        <div class="dataset-show-table-wrap">
                            <table class="dataset-show-table dataset-show-table--variables">
                                <thead><tr><th>Variabel</th><th>Definisi</th><th>Format</th><th>Akses</th></tr></thead>
                                <tbody>
                                    @foreach ($dataset->variables as $variable)
                                        <tr>
                                            <td data-label="Variabel">
                                                <code>{{ $variable->code }}</code>
                                                <strong><a href="{{ route('datasets.variables.show', [$dataset->slug, $variable]) }}">{{ $variable->name }}</a></strong>
                                            </td>
                                            <td data-label="Definisi">{{ $variable->definition ?: 'Definisi belum tersedia.' }}</td>
                                            <td data-label="Format"><strong>{{ $variable->unit ?: 'Tanpa satuan' }}</strong><small>{{ $typeNames[$variable->data_type] ?? ucfirst($variable->data_type) }}</small></td>
                                            <td data-label="Akses">
                                                <span class="dataset-show-status dataset-show-status--{{ $variable->access_tier }}">{{ $tierNames[$variable->access_tier] ?? ucfirst($variable->access_tier) }}</span>
                                                @if((float) $variable->price_per_cell > 0)
                                                    <small class="dataset-show-price">Rp{{ number_format((float) $variable->price_per_cell, 0, ',', '.') }}/sel</small>
                                                @else
                                                    <small class="dataset-show-price dataset-show-price--free">Tanpa biaya</small>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </section>

                <section class="dataset-show-card" id="preview" data-detail-reveal>
                    <header class="dataset-show-section-head">
                        <span class="dataset-show-section-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M4 19V9M10 19V5M16 19v-7M22 19H2"></path></svg></span>
                        <div>
                            <small>PRATINJAU OBSERVASI</small>
                            <h2>Lihat struktur datanya</h2>
                            <p>{{ $dataset->isOpen() ? 'Gunakan filter untuk memeriksa contoh data yang tersedia.' : 'Struktur ditampilkan tanpa membuka nilai yang masih dilindungi.' }}</p>
                        </div>
                    </header>

                    <form class="dataset-show-filter" method="GET" action="{{ route('datasets.show', $dataset->slug) }}#preview">
                        <label><span>Variabel</span><select name="variable"><option value="">Semua variabel</option>@foreach($dataset->variables as $variable)<option value="{{ $variable->id }}" @selected((string)request('variable') === (string)$variable->id)>{{ $variable->name }}</option>@endforeach</select></label>
                        <label><span>Periode</span><select name="period"><option value="">Semua periode</option>@foreach($periods as $period)<option value="{{ $period }}" @selected(request('period') === $period)>{{ $period }}</option>@endforeach</select></label>
                        <label><span>{{ $dataset->entityLabel() }}</span><select name="geography"><option value="">Semua {{ $dataset->entityLabelLower() }}</option>@foreach($geographies as $geography)<option value="{{ $geography->geography_code }}" @selected(request('geography') === $geography->geography_code)>{{ $geography->geography_name }}</option>@endforeach</select></label>
                        <button type="submit">Tampilkan <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4 10h12M11 5l5 5-5 5"></path></svg></button>
                    </form>

                    @if ($observations->isEmpty())
                        <div class="dataset-show-empty">Belum ada observasi untuk kombinasi filter ini.</div>
                    @else
                        <div class="dataset-show-table-wrap">
                            <table class="dataset-show-table dataset-show-table--preview">
                                <thead><tr><th>Variabel</th><th>{{ $dataset->entityLabel() }}</th><th>Periode</th><th>Nilai</th><th>Kualitas</th></tr></thead>
                                <tbody>
                                    @foreach($observations as $observation)
                                        <tr>
                                            <td data-label="Variabel"><code>{{ $observation->variable->code }}</code><strong>{{ $observation->variable->name }}</strong></td>
                                            <td data-label="{{ $dataset->entityLabel() }}">{{ $observation->geography_name }}</td>
                                            <td data-label="Periode">{{ $observation->period }}</td>
                                            <td data-label="Nilai">@if($dataset->isOpen())<strong>{{ $observation->displayValue() }}</strong> <small>{{ $observation->variable->unit }}</small>@else<span class="dataset-show-protected">Dilindungi</span>@endif</td>
                                            <td data-label="Kualitas"><span class="dataset-show-status dataset-show-status--{{ $observation->quality_status }}">{{ ucfirst($observation->quality_status) }}</span></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </section>

                <section class="dataset-show-card" data-detail-reveal>
                    <header class="dataset-show-section-head">
                        <span class="dataset-show-section-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M5 3h11l3 3v15H5V3Z"></path><path d="M9 10h6M9 14h6M9 18h4M15 3v4h4"></path></svg></span>
                        <div><small>METODOLOGI</small><h2>Asal dan cara pengolahan</h2></div>
                    </header>
                    <div class="dataset-show-prose">{!! nl2br(e($dataset->methodology ?: 'Metodologi belum dilengkapi oleh pengelola dataset.')) !!}</div>
                    @if($dataset->source_url)
                        <a class="dataset-show-source-link" href="{{ $dataset->source_url }}" target="_blank" rel="noopener noreferrer">Buka sumber asli <span>↗</span></a>
                    @endif
                </section>
            </main>

            <aside class="dataset-show-access" id="access" data-detail-reveal>
                <div class="dataset-show-access__sticky">
                    <div class="dataset-show-access__head">
                        <span class="dataset-show-access__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M12 3v12M7 10l5 5 5-5M5 20h14"></path></svg></span>
                        <div><small>{{ $dataset->isOpen() ? 'DATA TERBUKA' : 'PERMINTAAN AKSES' }}</small><h2>{{ $dataset->isOpen() ? 'Siapkan unduhan data' : 'Gunakan dataset ini' }}</h2></div>
                    </div>
                    <p class="dataset-show-access__intro">Pilih variabel dan cakupan yang benar-benar kamu perlukan untuk penelitian.</p>

                    @if($isCommercial)
                        <div class="dataset-show-license-note">
                            <span aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 2a10 10 0 100 20 10 10 0 000-20zm1 15.5V19h-2v-1.5a4.1 4.1 0 01-2.7-1.7l1.5-1.2c.7.9 1.5 1.3 2.5 1.3 1.1 0 1.8-.5 1.8-1.3 0-.7-.4-1.1-2.2-1.6-2.2-.7-3.2-1.6-3.2-3.3 0-1.7 1-2.8 2.3-3.2V5h2v1.4c1 .2 1.8.7 2.5 1.4l-1.4 1.3c-.6-.6-1.3-.9-2.1-.9-1 0-1.6.5-1.6 1.2 0 .7.5 1.1 2.3 1.7 2.1.7 3.1 1.6 3.1 3.3 0 1.6-1 2.8-2.8 3.1z"></path></svg></span>
                            <div><strong>Dataset berlisensi</strong><p>{{ config('bacadulu.payment.instruction') }}</p>@if($pricedVariables->isNotEmpty())<small>Biaya akhir mengikuti variabel dan cakupan yang disetujui.</small>@endif</div>
                        </div>
                    @endif

                    @if(! auth('web')->check() || ! auth('web')->user()->isResearcher())
                        <div class="dataset-show-login">
                            <span class="dataset-show-login__mark" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M7 10V8a5 5 0 0110 0v2M5 10h14v11H5V10Z"></path></svg></span>
                            <strong>Masuk untuk melanjutkan</strong>
                            <p>Akses dan riwayat unduhan akan tersimpan di akun peneliti.</p>
                            <a class="dataset-show-primary-button" href="{{ route('login', ['redirect' => request()->getRequestUri()]) }}">Masuk sebagai peneliti <span>→</span></a>
                            <a class="dataset-show-register-link" href="{{ route('register') }}">Belum punya akun? Daftar</a>
                        </div>
                    @elseif($dataset->variables->isEmpty())
                        <div class="dataset-show-empty">Belum ada variabel aktif yang dapat dipilih.</div>
                    @else
                        <form method="POST" action="{{ $dataset->isOpen() ? route('datasets.download.open', $dataset->slug) : route('datasets.request', $dataset->slug) }}" class="dataset-show-access-form" data-access-form>
                            @csrf
                            <fieldset>
                                <legend><span>Variabel</span><small data-selected-count>0 dipilih</small></legend>
                                <p>{{ $dataset->isOpen() ? 'Kosongkan bila ingin mengunduh semua variabel.' : 'Pilih minimal satu variabel.' }}</p>
                                <div class="dataset-show-check-list">
                                    @foreach($dataset->variables as $variable)
                                        <label><input type="checkbox" name="{{ $dataset->isOpen() ? 'variables[]' : 'variable_ids[]' }}" value="{{ $variable->id }}" @checked((string) request('variable') === (string) $variable->id)><span><strong>{{ $variable->name }}</strong><small>{{ $variable->code }} · {{ $variable->unit ?: 'tanpa satuan' }}</small></span><i aria-hidden="true"></i></label>
                                    @endforeach
                                </div>
                            </fieldset>

                            @if($geographies->isNotEmpty())
                                <fieldset>
                                    <legend><span>{{ $dataset->entityLabel() }}</span><small>Opsional</small></legend>
                                    <div class="dataset-show-check-list dataset-show-check-list--compact">@foreach($geographies as $geography)<label><input type="checkbox" name="geographies[]" value="{{ $geography->geography_code }}"><span>{{ $geography->geography_name }}</span><i aria-hidden="true"></i></label>@endforeach</div>
                                </fieldset>
                            @endif

                            @if($periods->isNotEmpty())
                                <fieldset>
                                    <legend><span>Periode</span><small>Opsional</small></legend>
                                    <div class="dataset-show-period-list">@foreach($periods as $period)<label><input type="checkbox" name="periods[]" value="{{ $period }}"><span>{{ $period }}</span></label>@endforeach</div>
                                </fieldset>
                            @endif

                            @unless($dataset->isOpen())
                                <label class="dataset-show-purpose" for="research_purpose"><span>Tujuan penggunaan</span><textarea id="research_purpose" name="research_purpose" rows="5" minlength="20" required placeholder="Ceritakan topik dan kebutuhan risetmu...">{{ old('research_purpose') }}</textarea></label>
                            @endunless

                            <button class="dataset-show-primary-button" type="submit"><span>{{ $dataset->isOpen() ? 'Unduh CSV' : 'Kirim permintaan akses' }}</span><svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4 10h12M11 5l5 5-5 5"></path></svg></button>
                        </form>
                    @endif

                    @if(! $dataset->isOpen())
                        <div class="dataset-show-urgent">
                            <div class="dataset-show-urgent__copy"><span>Butuh data lebih cepat?</span><p>Kirim permintaan, lalu konfirmasi ke admin untuk pengecekan prioritas.</p></div>
                            @if($whatsappUrl)
                                <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12.04 2a9.84 9.84 0 00-8.42 14.93L2 22l5.2-1.58A9.9 9.9 0 1012.04 2zm0 17.82a7.9 7.9 0 01-4.03-1.1l-.29-.17-3.09.94.97-3-.19-.31a7.81 7.81 0 116.63 3.64zm4.31-5.86c-.24-.12-1.4-.69-1.62-.77-.22-.08-.37-.12-.53.12-.16.24-.61.77-.75.93-.14.16-.28.18-.52.06-.24-.12-1-.37-1.9-1.18-.7-.63-1.18-1.4-1.32-1.64-.14-.24-.01-.37.1-.49.11-.11.24-.28.35-.41.12-.14.16-.24.24-.4.08-.15.04-.29-.02-.41-.06-.12-.53-1.28-.73-1.75-.19-.46-.39-.4-.53-.4h-.45c-.16 0-.41.06-.63.29-.22.24-.83.81-.83 1.97s.85 2.29.96 2.45c.12.16 1.66 2.54 4.03 3.56.56.24 1 .39 1.35.49.57.18 1.08.15 1.49.09.45-.07 1.4-.57 1.6-1.12.2-.55.2-1.02.14-1.12-.06-.1-.22-.16-.46-.28z"></path></svg>
                                    Chat admin BacaDulu
                                </a>
                            @else
                                <small>Nomor WhatsApp belum dikonfigurasi.</small>
                            @endif
                            <small>{{ $serviceHours }}</small>
                        </div>
                    @endif
                </div>
            </aside>
        </div>
    </section>
</div>
@endsection
