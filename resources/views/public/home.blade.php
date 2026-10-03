@extends('layouts.public')

@section('title', 'BacaDulu Dataset — Cari data penelitian')

@section(
    'meta_description',
    'Telusuri variabel penelitian dari perusahaan, ESG, BPS, dan statistik wilayah. Periksa definisi, periode, sumber, serta cara mengakses datanya.'
)

@push('page_scripts')
    <script
        src="{{ asset('assets/bacadulu-home.js') }}?v=17.1.0"
        defer
    ></script>
@endpush

@section('content')
<div class="home-v2" data-home-page>

    {{-- HERO --}}
    <section class="home-hero" aria-labelledby="home-title">
        <div class="container-wide home-hero__grid">

            <div class="home-hero__copy" data-home-reveal>
                <p class="home-kicker">
                    <span aria-hidden="true"></span>
                    Katalog data penelitian BacaDulu
                </p>

                <h1 id="home-title">
                    Temukan datanya.<br>
                    <span>Pahami sumbernya.</span>
                </h1>

                <p class="home-hero__lead">
                    Variabel perusahaan, ESG, dan statistik wilayah
                    dalam satu katalog. Periksa definisi, cakupan,
                    periode, serta sumber sebelum menggunakan datanya.
                </p>

                <form
                    class="home-search"
                    method="GET"
                    action="{{ route('datasets.index') }}"
                    role="search"
                >
                    <label for="home-query">
                        Variabel apa yang kamu cari?
                    </label>

                    <div class="home-search__row">
                        <input
                            id="home-query"
                            name="q"
                            type="search"
                            placeholder="Contoh: ROA, jumlah penduduk, inflasi"
                            maxlength="100"
                            autocomplete="off"
                            enterkeyhint="search"
                        >

                        <button type="submit">
                            Cari data
                            <span aria-hidden="true">↗</span>
                        </button>
                    </div>
                </form>

                <p class="home-hero__hint">
                    Belum tahu kata kuncinya?

                    <a href="{{ route('datasets.index') }}">
                        Jelajahi semua variabel
                        <span aria-hidden="true">→</span>
                    </a>
                </p>
            </div>

            <div
                class="home-paths"
                data-home-reveal
                aria-label="Pilihan cakupan katalog"
            >
                <div class="home-paths__header">
                    <span>ALUR DATA BACA DULU</span>
                    <small>Data masuk, konteks tetap terbaca</small>
                </div>

                <div class="home-paths__intro">
                    <div>
                        <h2>Beragam sumber.<br>Satu katalog yang rapi.</h2>

                        <p>
                            Data dikumpulkan, diperiksa, lalu disajikan
                            sebagai variabel yang siap ditelusuri.
                        </p>
                    </div>
                </div>

                <div
                    class="home-dataflow"
                    data-home-dataflow
                    aria-label="Ilustrasi alur data dari sumber menuju katalog"
                >
                    <svg
                        class="home-dataflow__map"
                        viewBox="0 0 560 190"
                        role="img"
                        aria-labelledby="home-dataflow-title"
                    >
                        <title id="home-dataflow-title">
                            CSV, BPS, dan API diproses menjadi katalog dataset
                        </title>

                        <defs>
                            <filter
                                id="home-flow-glow"
                                x="-50%"
                                y="-50%"
                                width="200%"
                                height="200%"
                            >
                                <feGaussianBlur
                                    stdDeviation="4"
                                    result="blur"
                                />

                                <feMerge>
                                    <feMergeNode in="blur" />
                                    <feMergeNode in="SourceGraphic" />
                                </feMerge>
                            </filter>
                        </defs>

                        <path
                            class="home-dataflow__line"
                            data-home-flow-path
                            d="M94 43 C158 43 184 73 239 87"
                        />

                        <path
                            class="home-dataflow__line"
                            data-home-flow-path
                            d="M94 95 H239"
                        />

                        <path
                            class="home-dataflow__line"
                            data-home-flow-path
                            d="M94 147 C158 147 184 117 239 103"
                        />

                        <path
                            class="home-dataflow__line home-dataflow__line--out"
                            data-home-flow-path
                            d="M319 95 H457"
                        />

                        <g
                            class="home-dataflow__source"
                            data-home-flow-source
                        >
                            <rect
                                x="18"
                                y="25"
                                width="76"
                                height="36"
                                rx="10"
                            />

                            <text x="56" y="47">CSV</text>
                        </g>

                        <g
                            class="home-dataflow__source"
                            data-home-flow-source
                        >
                            <rect
                                x="18"
                                y="77"
                                width="76"
                                height="36"
                                rx="10"
                            />

                            <text x="56" y="99">BPS</text>
                        </g>

                        <g
                            class="home-dataflow__source"
                            data-home-flow-source
                        >
                            <rect
                                x="18"
                                y="129"
                                width="76"
                                height="36"
                                rx="10"
                            />

                            <text x="56" y="151">API</text>
                        </g>

                        <g
                            class="home-dataflow__core"
                            data-home-flow-core
                        >
                            <circle
                                class="home-dataflow__core-ring"
                                cx="279"
                                cy="95"
                                r="47"
                            />

                            <ellipse
                                cx="279"
                                cy="75"
                                rx="22"
                                ry="8"
                            />

                            <path d="M257 75 V111 C257 116 267 120 279 120 C291 120 301 116 301 111 V75" />

                            <path d="M257 88 C257 93 267 97 279 97 C291 97 301 93 301 88" />

                            <path d="M257 101 C257 106 267 110 279 110 C291 110 301 106 301 101" />

                            <text x="279" y="144">PROSES</text>
                        </g>

                        <g
                            class="home-dataflow__output"
                            data-home-flow-output
                        >
                            <rect
                                x="457"
                                y="63"
                                width="86"
                                height="64"
                                rx="13"
                            />

                            <path d="M476 80 H525 M476 91 H514 M476 102 H521" />

                            <text x="500" y="146">
                                SIAP DITELUSURI
                            </text>
                        </g>

                        <circle
                            class="home-dataflow__packet"
                            data-home-flow-packet
                            data-start-x="94"
                            data-start-y="43"
                            data-end-x="249"
                            data-end-y="88"
                            cx="94"
                            cy="43"
                            r="5"
                        />

                        <circle
                            class="home-dataflow__packet"
                            data-home-flow-packet
                            data-start-x="94"
                            data-start-y="95"
                            data-end-x="249"
                            data-end-y="95"
                            cx="94"
                            cy="95"
                            r="5"
                        />

                        <circle
                            class="home-dataflow__packet"
                            data-home-flow-packet
                            data-start-x="94"
                            data-start-y="147"
                            data-end-x="249"
                            data-end-y="102"
                            cx="94"
                            cy="147"
                            r="5"
                        />

                        <circle
                            class="home-dataflow__packet home-dataflow__packet--out"
                            data-home-flow-packet
                            data-start-x="310"
                            data-start-y="95"
                            data-end-x="457"
                            data-end-y="95"
                            cx="310"
                            cy="95"
                            r="6"
                            filter="url(#home-flow-glow)"
                        />
                    </svg>

                    <div class="home-dataflow__caption">
                        <span>
                            <i></i>
                            Sumber masuk
                        </span>

                        <span>
                            <i></i>
                            Validasi konteks
                        </span>

                        <span>
                            <i></i>
                            Katalog siap
                        </span>
                    </div>
                </div>

                <p class="home-paths__choose">
                    PILIH CAKUPAN DATA
                </p>

                <div class="home-paths__list">
                    <a
                        class="home-path"
                        href="{{ route('datasets.index', ['scope' => 'corporate']) }}"
                    >
                        <span
                            class="home-path__index home-path__index--corporate"
                            aria-hidden="true"
                        >
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                            >
                                <path d="M4 21h16" />
                                <path d="M6.5 21V5.5c0-.8.6-1.5 1.4-1.5h8.2c.8 0 1.4.7 1.4 1.5V21" />
                                <path d="M9 8h1.5M13.5 8H15M9 11.5h1.5M13.5 11.5H15M9 15h1.5M13.5 15H15" />
                                <path d="M10 21v-3h4v3" />
                            </svg>
                        </span>

                        <span class="home-path__body">
                            <small>PERUSAHAAN &amp; ESG</small>

                            <strong>
                                Data bisnis dan keberlanjutan
                            </strong>

                            <span>
                                Keuangan, emiten, sektor, tata kelola,
                                dan ESG.
                            </span>
                        </span>

                        <span
                            class="home-path__arrow"
                            aria-hidden="true"
                        >
                            ↗
                        </span>
                    </a>

                    <a
                        class="home-path"
                        href="{{ route('datasets.index', ['scope' => 'regional']) }}"
                    >
                        <span
                            class="home-path__index home-path__index--statistics"
                            aria-hidden="true"
                        >
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                            >
                                <path d="M4 20V10" />
                                <path d="M9.3 20V5" />
                                <path d="M14.7 20v-7" />
                                <path d="M20 20V8" />
                                <path d="M3 20h18" />
                                <path d="M4 7.5 9.3 3l5.4 6 5.3-4" />
                                <path d="m17.8 5 2.2-.1-.1 2.2" />
                            </svg>
                        </span>

                        <span class="home-path__body">
                            <small>BPS &amp; STATISTIK WILAYAH</small>

                            <strong>
                                Data ekonomi dan kependudukan
                            </strong>

                            <span>
                                Indikator nasional, provinsi, dan
                                kabupaten/kota.
                            </span>
                        </span>

                        <span
                            class="home-path__arrow"
                            aria-hidden="true"
                        >
                            ↗
                        </span>
                    </a>
                </div>

                <div class="home-paths__meta">
                    <span>Definisi tersedia</span>
                    <span>Sumber tercatat</span>
                    <span>Akses transparan</span>
                </div>
            </div>
        </div>

        <div
            class="container-wide home-hero__foot"
            aria-label="Standar informasi"
        >
            <span>Definisi variabel</span>
            <span>Sumber tercatat</span>
            <span>Periode jelas</span>
            <span>Akses transparan</span>
        </div>
    </section>

    {{-- STATISTIK KATALOG --}}
    <section
        class="home-status"
        aria-label="Isi katalog saat ini"
    >
        <div class="container-wide home-status__inner">
            <div class="home-status__caption">
                <span class="home-status__eyebrow">
                    RINGKASAN KATALOG
                </span>

                <strong>
                    Data yang siap kamu telusuri.
                </strong>

                <p>
                    Angka ringkas untuk melihat isi katalog tanpa
                    mengalihkan perhatian dari pencarian data.
                </p>
            </div>

            <div class="home-status__summary">
                <div class="home-status__primary">
                    <span
                        class="home-status__icon"
                        aria-hidden="true"
                    >
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                        >
                            <path d="M5 5h14v14H5z" />
                            <path d="M8 9h8M8 12h8M8 15h5" />
                        </svg>
                    </span>

                    <span class="home-status__value">
                        <strong data-home-counter="{{ (int) $stats['variables'] }}">
                            {{ number_format($stats['variables'], 0, ',', '.') }}
                        </strong>

                        <small>
                            variabel siap ditelusuri
                        </small>
                    </span>
                </div>

                <div
                    class="home-status__assurances"
                    aria-label="Kualitas katalog"
                >
                    <span class="home-status__chip">
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                        >
                            <path d="m5 12 4 4L19 6" />
                        </svg>

                        Observasi ditinjau
                    </span>

                    <span class="home-status__chip">
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                        >
                            <path d="M12 3 4.5 6v5.4c0 4.7 3.2 8.1 7.5 9.6 4.3-1.5 7.5-4.9 7.5-9.6V6L12 3Z" />
                            <path d="m8.5 12 2.2 2.2 4.8-5" />
                        </svg>

                        Sumber terverifikasi
                    </span>
                </div>
            </div>
        </div>
    </section>

    {{-- VARIABEL PILIHAN --}}
    <section
        class="home-section home-featured"
        aria-labelledby="home-featured-heading"
    >
        <div class="container-wide">
            <div
                class="home-section__heading"
                data-home-reveal
            >
                <div>
                    <span class="home-label">
                        LANGSUNG JELAJAHI
                    </span>

                    <h2 id="home-featured-heading">
                        Mulai dari variabel,<br>
                        bukan dari nama file.
                    </h2>
                </div>

                <div class="home-section__side">
                    <p>
                        Periksa data yang tersedia beserta cakupan dan
                        sumbernya sebelum mengunduh atau meminta akses.
                    </p>

                    <a href="{{ route('datasets.index') }}">
                        Buka katalog lengkap
                        <span aria-hidden="true">↗</span>
                    </a>
                </div>
            </div>

            @if($featuredVariables->isEmpty())
                <div
                    class="home-empty"
                    data-home-reveal
                >
                    <span
                        class="home-empty__mark"
                        aria-hidden="true"
                    >
                        —
                    </span>

                    <div>
                        <strong>
                            Belum ada variabel terbit.
                        </strong>

                        <p>
                            Katalog akan menampilkan variabel setelah
                            data ditinjau dan dipublikasikan.
                        </p>
                    </div>

                    <a href="{{ route('datasets.index') }}">
                        Buka katalog
                        <span aria-hidden="true">→</span>
                    </a>
                </div>
            @else
                <div
                    class="home-featured__list variable-list variable-list--featured"
                    data-home-reveal
                >
                    @foreach($featuredVariables as $variable)
                        @include(
                            'partials.variable-row',
                            ['variable' => $variable]
                        )
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- CARA KERJA --}}
    <section
        class="home-section home-how"
        id="cara-kerja"
        aria-labelledby="home-how-heading"
    >
        <div class="container-wide home-how__grid">
            <div data-home-reveal>
                <span class="home-label">
                    CARA KERJA
                </span>

                <h2 id="home-how-heading">
                    Tiga langkah<br>
                    menuju data.
                </h2>

                <p class="home-how__intro">
                    Tidak perlu memahami struktur dataset di balik
                    layar. Mulailah dari indikator yang kamu butuhkan.
                </p>

                <a
                    class="home-text-link"
                    href="{{ route('datasets.index') }}"
                >
                    Mulai cari variabel
                    <span aria-hidden="true">→</span>
                </a>
            </div>

            <ol
                class="home-steps"
                data-home-reveal
            >
                <li>
                    <span>01</span>

                    <div>
                        <h3>Cari variabel</h3>

                        <p>
                            Tulis indikator atau jelajahi katalog
                            Perusahaan &amp; ESG maupun statistik wilayah.
                        </p>
                    </div>
                </li>

                <li>
                    <span>02</span>

                    <div>
                        <h3>Periksa kecocokannya</h3>

                        <p>
                            Lihat definisi, satuan, cakupan, periode,
                            status kualitas, dan sumbernya.
                        </p>
                    </div>
                </li>

                <li>
                    <span>03</span>

                    <div>
                        <h3>Dapatkan datanya</h3>

                        <p>
                            Unduh data terbuka atau ajukan permintaan
                            untuk data dengan akses terbatas.
                        </p>
                    </div>
                </li>
            </ol>
        </div>
    </section>

    {{-- STANDAR DATA --}}
    <section
        class="home-section home-standards"
        id="standar-data"
        aria-labelledby="home-standards-heading"
    >
        <div class="container-wide">
            <div
                class="home-section__heading"
                data-home-reveal
            >
                <div>
                    <span class="home-label">
                        KENAPA BACA DULU?
                    </span>

                    <h2 id="home-standards-heading">
                        Angka saja belum cukup.
                    </h2>
                </div>

                <p>
                    Data penelitian yang berguna harus dapat diperiksa
                    asal, arti, dan batas penggunaannya.
                </p>
            </div>

            <div class="home-standards__grid">
                <article data-home-reveal>
                    <div class="home-standard__top">
                        <span
                            class="home-standard__icon"
                            aria-hidden="true"
                        >
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                            >
                                <path d="M5 4.5h11.5A2.5 2.5 0 0 1 19 7v12.5H7.5A2.5 2.5 0 0 1 5 17V4.5Z" />
                                <path d="M8 8h7M8 11.5h7M8 15h4.5" />
                                <path d="M5 17c0-1.4 1.1-2.5 2.5-2.5H19" />
                            </svg>
                        </span>

                        <span>DEFINISI</span>
                    </div>

                    <h3>Tahu apa yang diukur</h3>

                    <p>
                        Setiap variabel disertai satuan dan keterangan
                        agar angka tidak kehilangan konteks.
                    </p>
                </article>

                <article data-home-reveal>
                    <div class="home-standard__top">
                        <span
                            class="home-standard__icon"
                            aria-hidden="true"
                        >
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                            >
                                <circle cx="12" cy="12" r="3" />
                                <circle cx="5" cy="6" r="2" />
                                <circle cx="19" cy="6" r="2" />
                                <circle cx="5" cy="18" r="2" />
                                <circle cx="19" cy="18" r="2" />
                                <path d="m6.7 7.2 3.1 3M17.3 7.2l-3.1 3M6.7 16.8l3.1-3M17.3 16.8l-3.1-3" />
                            </svg>
                        </span>

                        <span>SUMBER</span>
                    </div>

                    <h3>Bisa telusuri asalnya</h3>

                    <p>
                        Koleksi dan penyedia data ditampilkan, termasuk
                        cakupan entitas dan periodenya.
                    </p>
                </article>

                <article data-home-reveal>
                    <div class="home-standard__top">
                        <span
                            class="home-standard__icon"
                            aria-hidden="true"
                        >
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                            >
                                <rect
                                    x="4"
                                    y="10"
                                    width="16"
                                    height="11"
                                    rx="3"
                                />

                                <path d="M8 10V7a4 4 0 0 1 7.7-1.5" />
                                <path d="M12 14v3" />
                            </svg>
                        </span>

                        <span>AKSES</span>
                    </div>

                    <h3>Aturannya jelas</h3>

                    <p>
                        Data terbuka dapat digunakan langsung. Akses
                        terbatas melalui persetujuan pengelola.
                    </p>
                </article>
            </div>
        </div>
    </section>

    {{-- PENUTUP --}}
    <section
        class="home-outro"
        aria-labelledby="home-outro-heading"
    >
        <div
            class="container-wide home-outro__inner"
            data-home-reveal
        >
            <div>
                <span class="home-label">
                    SIAP MULAI?
                </span>

                <h2 id="home-outro-heading">
                    Pertanyaan riset yang baik<br>
                    membutuhkan data yang dapat ditelusuri.
                </h2>
            </div>

            <div class="home-outro__side">
                <div
                    class="home-outro__preview"
                    data-home-outro-preview
                    aria-label="Preview dataset yang sedang divalidasi"
                >
                    <div class="home-outro__preview-head">
                        <span>
                            <i></i>
                            DATASET PREVIEW
                        </span>

                        <small>LIVE REVIEW</small>
                    </div>

                    <div class="home-outro__preview-window">
                        <div class="home-outro__preview-toolbar">
                            <span
                                class="home-outro__preview-dots"
                                aria-hidden="true"
                            >
                                <i></i>
                                <i></i>
                                <i></i>
                            </span>

                            <strong>
                                dataset_variable.csv
                            </strong>

                            <span class="home-outro__preview-lock">
                                TERKURASI
                            </span>
                        </div>

                        <div
                            class="home-outro__preview-table"
                            role="table"
                            aria-label="Contoh variabel dataset"
                        >
                            <div
                                class="home-outro__preview-row home-outro__preview-row--head"
                                role="row"
                            >
                                <span role="columnheader">
                                    Variabel
                                </span>

                                <span role="columnheader">
                                    Periode
                                </span>

                                <span role="columnheader">
                                    Status
                                </span>
                            </div>

                            <div
                                class="home-outro__preview-row"
                                data-home-preview-row
                                role="row"
                            >
                                <strong role="cell">
                                    ROA
                                </strong>

                                <span role="cell">
                                    2019—2023
                                </span>

                                <span
                                    class="home-outro__preview-status"
                                    role="cell"
                                >
                                    <i></i>
                                    siap
                                </span>
                            </div>

                            <div
                                class="home-outro__preview-row"
                                data-home-preview-row
                                role="row"
                            >
                                <strong role="cell">
                                    Inflasi
                                </strong>

                                <span role="cell">
                                    2015—2024
                                </span>

                                <span
                                    class="home-outro__preview-status"
                                    role="cell"
                                >
                                    <i></i>
                                    siap
                                </span>
                            </div>

                            <div
                                class="home-outro__preview-row"
                                data-home-preview-row
                                role="row"
                            >
                                <strong role="cell">
                                    Jumlah penduduk
                                </strong>

                                <span role="cell">
                                    2010—2024
                                </span>

                                <span
                                    class="home-outro__preview-status"
                                    role="cell"
                                >
                                    <i></i>
                                    siap
                                </span>
                            </div>

                            <span
                                class="home-outro__preview-scan"
                                data-home-preview-scan
                                aria-hidden="true"
                            ></span>
                        </div>
                    </div>

                    <div class="home-outro__preview-foot">
                        <span>
                            <i></i>
                            Definisi terbaca
                        </span>

                        <span>
                            <i></i>
                            Sumber tercatat
                        </span>

                        <span>
                            <i></i>
                            Siap ditelusuri
                        </span>
                    </div>
                </div>

                <div class="home-outro__actions">
                    <a
                        class="home-outro__primary"
                        href="{{ route('datasets.index') }}"
                    >
                        Jelajahi katalog
                        <span aria-hidden="true">↗</span>
                    </a>

                    @if(
                        auth('web')->check()
                        && auth('web')->user()->isResearcher()
                    )
                        <a
                            class="home-outro__secondary"
                            href="{{ route('user.profile') }}"
                        >
                            Profil saya
                            <span aria-hidden="true">→</span>
                        </a>
                    @else
                        <a
                            class="home-outro__secondary"
                            href="{{ route('register') }}"
                        >
                            Buat akun peneliti
                            <span aria-hidden="true">→</span>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </section>

</div>
@endsection