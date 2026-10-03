@extends('layouts.public')

@section('title', 'Katalog Variabel — BacaDulu Dataset')
@section('meta_description', 'Temukan variabel perusahaan, ESG, ekonomi, sosial, dan statistik wilayah untuk kebutuhan riset.')

@push('page_styles')
    <link rel="stylesheet" href="{{ asset('assets/bacadulu-catalog.css') }}?v=1.0.0">
    <link rel="stylesheet" href="{{ asset('assets/bacadulu-catalog-results.css') }}?v=1.0.0">
@endpush

@push('page_scripts')
    <script src="{{ asset('assets/bacadulu-catalog.js') }}?v=1.0.0" defer></script>
@endpush

@section('content')
@php
    $filterKeys = [
        'q',
        'scope',
        'category',
        'provider',
        'period',
        'entity',
        'access',
    ];

    $activeFilterCount = collect($filterKeys)
        ->filter(fn ($key) => request()->filled($key))
        ->count();

    $accessNames = [
        'open' => 'Terbuka',
        'restricted' => 'Terbatas',
        'commercial' => 'Berlisensi',
    ];

    $scopeNames = [
        'corporate' => 'Perusahaan & ESG',
        'regional' => 'Statistik wilayah',
    ];

    $selectedProvider = $providers->firstWhere(
        'id',
        (int) request('provider')
    );

    $activeFilters = collect([
        'q' => request('q')
            ? 'Kata kunci · '.request('q')
            : null,

        'scope' => request('scope')
            ? ($scopeNames[request('scope')] ?? request('scope'))
            : null,

        'category' => request('category')
            ? 'Topik · '.request('category')
            : null,

        'provider' => request('provider')
            ? 'Sumber · '.($selectedProvider?->name ?? request('provider'))
            : null,

        'period' => request('period')
            ? 'Periode · '.request('period')
            : null,

        'entity' => request('entity')
            ? 'Entitas · '.request('entity')
            : null,

        'access' => request('access')
            ? 'Akses · '.($accessNames[request('access')] ?? request('access'))
            : null,
    ])->filter();

    $hasQuery = request()->hasAny($filterKeys);
@endphp

<div class="catalog-v2" data-catalog-page>
    <section class="catalog-v2__hero">
        <div
            class="catalog-v2__orb catalog-v2__orb--left"
            aria-hidden="true"
        ></div>

        <div
            class="catalog-v2__orb catalog-v2__orb--right"
            aria-hidden="true"
        ></div>

        <div class="container-wide catalog-v2__hero-grid">
            <div
                class="catalog-v2__hero-copy"
                data-catalog-hero-copy
            >
                <p class="catalog-v2__kicker">
                    <span aria-hidden="true"></span>
                    Katalog variabel penelitian
                </p>

                <h1>
                    Temukan variabel.<br>
                    <span>Susun risetmu.</span>
                </h1>

                <p class="catalog-v2__lead">
                    Cari indikator perusahaan, ESG, ekonomi, sosial,
                    dan wilayah. Periksa definisi serta sumbernya
                    sebelum digunakan.
                </p>

                <form
                    class="catalog-quick-search"
                    method="GET"
                    action="{{ route('datasets.index') }}"
                    role="search"
                >
                    @if(request('scope'))
                        <input
                            type="hidden"
                            name="scope"
                            value="{{ request('scope') }}"
                        >
                    @endif

                    <label for="catalog-main-search">
                        Cari variabel
                    </label>

                    <div class="catalog-quick-search__control">
                        <svg
                            aria-hidden="true"
                            viewBox="0 0 24 24"
                            fill="none"
                        >
                            <circle
                                cx="11"
                                cy="11"
                                r="6.5"
                            ></circle>

                            <path d="m16 16 4 4"></path>
                        </svg>

                        <input
                            id="catalog-main-search"
                            name="q"
                            type="search"
                            value="{{ request('q') }}"
                            placeholder="ROA, emisi scope 1, jumlah penduduk"
                            maxlength="100"
                            autocomplete="off"
                            data-catalog-search
                        >

                        <button type="submit">
                            <span>Cari variabel</span>

                            <svg
                                aria-hidden="true"
                                viewBox="0 0 20 20"
                                fill="none"
                            >
                                <path d="M4 10h12M11 5l5 5-5 5"></path>
                            </svg>
                        </button>
                    </div>
                </form>

                <div class="catalog-v2__trust">
                    <span>
                        <i aria-hidden="true"></i>
                        Definisi jelas
                    </span>

                    <span>
                        <i aria-hidden="true"></i>
                        Sumber tertelusur
                    </span>

                    <span>
                        <i aria-hidden="true"></i>
                        Siap diperiksa
                    </span>
                </div>
            </div>

            <div
                class="catalog-data-scene"
                data-catalog-scene
                aria-hidden="true"
            >
                <div class="catalog-data-scene__topline">
                    <span>DATA EXPLORER</span>

                    <b>
                        <i></i>
                        LIVE CATALOG
                    </b>
                </div>

                <div class="catalog-data-scene__canvas">
                    <svg
                        class="catalog-data-scene__links"
                        viewBox="0 0 520 360"
                        preserveAspectRatio="none"
                    >
                        <path
                            data-data-line
                            d="M92 76 C178 76 175 156 260 170"
                        ></path>

                        <path
                            data-data-line
                            d="M92 180 C170 180 184 176 260 170"
                        ></path>

                        <path
                            data-data-line
                            d="M92 284 C178 284 175 196 260 182"
                        ></path>

                        <path
                            data-data-line
                            d="M286 176 C355 176 365 110 430 110"
                        ></path>

                        <path
                            data-data-line
                            d="M286 180 C355 180 365 252 430 252"
                        ></path>
                    </svg>

                    <div
                        class="catalog-data-node catalog-data-node--source catalog-data-node--one"
                        data-data-node
                    >
                        <span>
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                            >
                                <path d="M4 20V9l8-5 8 5v11M8 20v-6h8v6M9 10h.01M15 10h.01"></path>
                            </svg>
                        </span>

                        <div>
                            <small>Sumber 01</small>
                            <strong>Perusahaan</strong>
                        </div>
                    </div>

                    <div
                        class="catalog-data-node catalog-data-node--source catalog-data-node--two"
                        data-data-node
                    >
                        <span>
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                            >
                                <path d="M12 3v18M4 8h16M6 8l-2 5h4L6 8Zm12 0-2 5h4l-2-5ZM8 21h8"></path>
                            </svg>
                        </span>

                        <div>
                            <small>Sumber 02</small>
                            <strong>ESG</strong>
                        </div>
                    </div>

                    <div
                        class="catalog-data-node catalog-data-node--source catalog-data-node--three"
                        data-data-node
                    >
                        <span>
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                            >
                                <path d="M4 20V7l4-3 4 3v13M12 10l4-3 4 3v10M2 20h20M7 9h2M7 13h2M15 12h2M15 16h2"></path>
                            </svg>
                        </span>

                        <div>
                            <small>Sumber 03</small>
                            <strong>Statistik wilayah</strong>
                        </div>
                    </div>

                    <div
                        class="catalog-data-core"
                        data-data-core
                    >
                        <span class="catalog-data-core__ring"></span>

                        <span
                            class="catalog-data-core__ring catalog-data-core__ring--two"
                        ></span>

                        <svg
                            viewBox="0 0 48 48"
                            fill="none"
                        >
                            <path d="M11 14c0-3 6-6 13-6s13 3 13 6-6 6-13 6-13-3-13-6Z"></path>

                            <path d="M11 14v10c0 3 6 6 13 6s13-3 13-6V14M11 24v10c0 3 6 6 13 6s13-3 13-6V24"></path>
                        </svg>

                        <strong>DATA</strong>
                    </div>

                    <div
                        class="catalog-data-output catalog-data-output--one"
                        data-data-output
                    >
                        <span>VARIABLE</span>
                        <b>ROA</b>
                        <i></i>
                    </div>

                    <div
                        class="catalog-data-output catalog-data-output--two"
                        data-data-output
                    >
                        <span>VARIABLE</span>
                        <b>POPULATION</b>
                        <i></i>
                    </div>

                    <span
                        class="catalog-data-pulse catalog-data-pulse--one"
                        data-data-pulse
                    ></span>

                    <span
                        class="catalog-data-pulse catalog-data-pulse--two"
                        data-data-pulse
                    ></span>

                    <span
                        class="catalog-data-pulse catalog-data-pulse--three"
                        data-data-pulse
                    ></span>
                </div>

                <div class="catalog-data-scene__footer">
                    <span>Source</span>
                    <i></i>
                    <span>Validation</span>
                    <i></i>
                    <span>Ready</span>
                </div>
            </div>
        </div>
    </section>

    <section
        class="catalog-v2__explore"
        aria-labelledby="catalog-explore-title"
    >
        <div class="container-wide">
            <div
                class="catalog-section-heading"
                data-reveal
            >
                <div>
                    <span class="catalog-section-heading__eyebrow">
                        Pilih jenis data
                    </span>

                    <h2 id="catalog-explore-title">
                        Mulai dari ruang lingkup risetmu
                    </h2>
                </div>
            </div>

            <nav
                class="catalog-scope-grid"
                aria-label="Cakupan data"
            >
                <a
                    class="catalog-scope-card {{ request('scope') ? '' : 'is-active' }}"
                    href="{{ route('datasets.index', request()->except('scope', 'page')) }}"
                    @if(!request('scope')) aria-current="page" @endif
                >
                    <span class="catalog-scope-card__icon">
                        <svg
                            aria-hidden="true"
                            viewBox="0 0 24 24"
                            fill="none"
                        >
                            <rect
                                x="3"
                                y="4"
                                width="7"
                                height="7"
                                rx="1"
                            ></rect>

                            <rect
                                x="14"
                                y="4"
                                width="7"
                                height="7"
                                rx="1"
                            ></rect>

                            <rect
                                x="3"
                                y="15"
                                width="7"
                                height="6"
                                rx="1"
                            ></rect>

                            <rect
                                x="14"
                                y="15"
                                width="7"
                                height="6"
                                rx="1"
                            ></rect>
                        </svg>
                    </span>

                    <span class="catalog-scope-card__copy">
                        <small>Seluruh koleksi</small>
                        <strong>Semua data</strong>
                        <p>Seluruh variabel terbit.</p>
                    </span>

                    <span
                        class="catalog-scope-card__arrow"
                        aria-hidden="true"
                    >
                        ↗
                    </span>
                </a>

                <a
                    class="catalog-scope-card {{ request('scope') === 'corporate' ? 'is-active' : '' }}"
                    href="{{ route('datasets.index', array_merge(request()->except('scope', 'page'), ['scope' => 'corporate'])) }}"
                    @if(request('scope') === 'corporate') aria-current="page" @endif
                >
                    <span class="catalog-scope-card__icon">
                        <svg
                            aria-hidden="true"
                            viewBox="0 0 24 24"
                            fill="none"
                        >
                            <path d="M4 21V8l8-5 8 5v13M8 21v-6h8v6M8 10h1M15 10h1"></path>
                            <path d="M2 21h20"></path>
                        </svg>
                    </span>

                    <span class="catalog-scope-card__copy">
                        <small>Emiten & keberlanjutan</small>
                        <strong>Perusahaan & ESG</strong>
                        <p>Data emiten dan ESG.</p>
                    </span>

                    <span
                        class="catalog-scope-card__arrow"
                        aria-hidden="true"
                    >
                        ↗
                    </span>
                </a>

                <a
                    class="catalog-scope-card {{ request('scope') === 'regional' ? 'is-active' : '' }}"
                    href="{{ route('datasets.index', array_merge(request()->except('scope', 'page'), ['scope' => 'regional'])) }}"
                    @if(request('scope') === 'regional') aria-current="page" @endif
                >
                    <span class="catalog-scope-card__icon">
                        <svg
                            aria-hidden="true"
                            viewBox="0 0 24 24"
                            fill="none"
                        >
                            <path d="M12 21s7-5.1 7-11a7 7 0 1 0-14 0c0 5.9 7 11 7 11Z"></path>
                            <circle
                                cx="12"
                                cy="10"
                                r="2.5"
                            ></circle>
                        </svg>
                    </span>

                    <span class="catalog-scope-card__copy">
                        <small>BPS & pemerintahan</small>
                        <strong>Statistik wilayah</strong>
                        <p>Data BPS dan wilayah.</p>
                    </span>

                    <span
                        class="catalog-scope-card__arrow"
                        aria-hidden="true"
                    >
                        ↗
                    </span>
                </a>
            </nav>

            <div
                class="catalog-filter-shell"
                data-reveal
            >
                <button
                    class="catalog-filter-toggle-v2"
                    type="button"
                    aria-controls="catalog-filter-panel"
                    aria-expanded="false"
                    data-filter-toggle
                    data-start-open="{{ $activeFilterCount > 0 ? 'true' : 'false' }}"
                >
                    <span class="catalog-filter-toggle-v2__icon">
                        <svg
                            aria-hidden="true"
                            viewBox="0 0 24 24"
                            fill="none"
                        >
                            <path d="M4 6h16M7 12h10M10 18h4"></path>
                        </svg>
                    </span>

                    <span>
                        <strong>Filter lanjutan</strong>
                    </span>

                    <b>
                        {{ $activeFilterCount > 0
                            ? $activeFilterCount.' aktif'
                            : 'Tampilkan' }}
                    </b>

                    <svg
                        class="catalog-filter-toggle-v2__chevron"
                        aria-hidden="true"
                        viewBox="0 0 20 20"
                        fill="none"
                    >
                        <path d="m5 7 5 5 5-5"></path>
                    </svg>
                </button>

                <form
                    id="catalog-filter-panel"
                    class="catalog-filter catalog-filter--variables catalog-filter-v2"
                    method="GET"
                    action="{{ route('datasets.index') }}"
                    data-filter-panel
                >
                    @if(request('scope'))
                        <input
                            type="hidden"
                            name="scope"
                            value="{{ request('scope') }}"
                        >
                    @endif

                    <div class="field field--search field--wide">
                        <label for="q">Cari variabel</label>

                        <input
                            id="q"
                            name="q"
                            type="search"
                            value="{{ request('q') }}"
                            placeholder="ROA, emisi, jumlah penduduk"
                            maxlength="100"
                        >
                    </div>

                    <div class="field">
                        <label for="category">Topik</label>

                        <select
                            id="category"
                            name="category"
                        >
                            <option value="">Semua topik</option>

                            @foreach ($categories as $category)
                                <option
                                    value="{{ $category }}"
                                    @selected(request('category') === $category)
                                >
                                    {{ $category }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field">
                        <label for="provider">Sumber</label>

                        <select
                            id="provider"
                            name="provider"
                        >
                            <option value="">Semua sumber</option>

                            @foreach ($providers as $provider)
                                <option
                                    value="{{ $provider->id }}"
                                    @selected((string) request('provider') === (string) $provider->id)
                                >
                                    {{ $provider->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field">
                        <label for="period">Periode</label>

                        <select
                            id="period"
                            name="period"
                        >
                            <option value="">Semua periode</option>

                            @foreach ($periods as $period)
                                <option
                                    value="{{ $period }}"
                                    @selected(request('period') === $period)
                                >
                                    {{ $period }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field">
                        <label for="entity">
                            Perusahaan atau wilayah
                        </label>

                        <input
                            id="entity"
                            name="entity"
                            value="{{ request('entity') }}"
                            maxlength="100"
                            placeholder="BBCA, Jawa Barat, Indonesia"
                        >
                    </div>

                    <div class="field">
                        <label for="access">Akses</label>

                        <select
                            id="access"
                            name="access"
                        >
                            <option value="">Semua akses</option>

                            <option
                                value="open"
                                @selected(request('access') === 'open')
                            >
                                Terbuka
                            </option>

                            <option
                                value="restricted"
                                @selected(request('access') === 'restricted')
                            >
                                Terbatas
                            </option>

                            <option
                                value="commercial"
                                @selected(request('access') === 'commercial')
                            >
                                Berlisensi
                            </option>
                        </select>
                    </div>

                    <div class="catalog-filter-v2__actions">
                        <a href="{{ route('datasets.index', request('scope') ? ['scope' => request('scope')] : []) }}">
                            Reset
                        </a>

                        <button type="submit">
                            Terapkan filter
                            <span aria-hidden="true">→</span>
                        </button>
                    </div>
                </form>
            </div>

            @if($activeFilters->isNotEmpty())
                <div class="catalog-active-filters-v2">
                    <span>Filter aktif</span>

                    @foreach($activeFilters as $key => $label)
                        <a href="{{ route('datasets.index', request()->except($key, 'page')) }}">
                            {{ $label }}
                            <b aria-hidden="true">×</b>
                        </a>
                    @endforeach

                    <a
                        class="catalog-active-filters-v2__reset"
                        href="{{ route('datasets.index') }}"
                    >
                        Reset semua
                    </a>
                </div>
            @endif
        </div>
    </section>

    <section
        class="catalog-results"
        aria-labelledby="catalog-results-title"
    >
        <div class="container-wide">
            <header
                class="catalog-results__head"
                data-reveal
            >
                <div>
                    <span class="catalog-results__count">
                        {{ number_format($variables->total()) }}
                    </span>

                    <span>
                        <strong id="catalog-results-title">
                            Variabel ditemukan
                        </strong>

                        <small>
                            {{ $hasQuery
                                ? 'Sesuai pencarianmu.'
                                : 'Siap ditelusuri.' }}
                        </small>
                    </span>
                </div>
            </header>

            @if ($variables->isEmpty())
                <div
                    class="catalog-empty"
                    data-reveal
                >
                    <span class="catalog-empty__icon">
                        <svg
                            aria-hidden="true"
                            viewBox="0 0 32 32"
                            fill="none"
                        >
                            <circle
                                cx="14"
                                cy="14"
                                r="8"
                            ></circle>

                            <path d="m20 20 7 7M10 14h8"></path>
                        </svg>
                    </span>

                    <small>Tidak ada hasil</small>

                    <h3>Variabel belum ditemukan.</h3>

                    <p>
                        Coba kata kunci lain atau hapus sebagian filter.
                    </p>

                    <a href="{{ route('datasets.index') }}">
                        Lihat semua variabel
                    </a>
                </div>
            @else
                <div class="variable-list catalog-variable-grid">
                    @foreach ($variables as $variable)
                        @include('partials.catalog-variable-card', ['variable' => $variable])
                    @endforeach
                </div>

                <div class="pagination-wrap catalog-pagination">
                    {{ $variables->links() }}
                </div>
            @endif
        </div>
    </section>
</div>
@endsection