@extends('layouts.public')

@section('title', 'BacaDulu Dataset — Cari data penelitian')
@section('meta_description', 'Telusuri variabel penelitian dari perusahaan, ESG, BPS, dan statistik wilayah. Periksa definisi, periode, sumber, serta cara mengakses datanya.')

@push('page_styles')
    <link rel="stylesheet" href="{{ asset('assets/bacadulu-home.css') }}?v=9.1.0">
@endpush

@push('page_scripts')
    <script src="{{ asset('assets/bacadulu-home.js') }}?v=9.1.0" defer></script>
@endpush

@section('content')
<div class="home-v2" data-home-page>
    <section class="home-hero" aria-labelledby="home-title">
        <div class="container-wide home-hero__grid">
            <div class="home-hero__copy" data-home-reveal>
                <p class="home-kicker"><span aria-hidden="true"></span> Katalog data penelitian BacaDulu</p>
                <h1 id="home-title">Temukan datanya.<br><span>Pahami sumbernya.</span></h1>
                <p class="home-hero__lead">Variabel perusahaan, ESG, dan statistik wilayah dalam satu katalog. Lihat definisi, cakupan, periode, dan sumber sebelum memakai datanya.</p>

                <form class="home-search" method="GET" action="{{ route('datasets.index') }}" role="search">
                    <label for="home-query">Variabel apa yang kamu cari?</label>
                    <div class="home-search__row">
                        <input id="home-query" name="q" type="search" placeholder="Contoh: ROA, jumlah penduduk, inflasi" maxlength="100" autocomplete="off" enterkeyhint="search">
                        <button type="submit">Cari data <span aria-hidden="true">↗</span></button>
                    </div>
                </form>
                <p class="home-hero__hint">Belum tahu kata kuncinya? <a href="{{ route('datasets.index') }}">Jelajahi semua variabel <span aria-hidden="true">→</span></a></p>
            </div>

            <div class="home-paths" data-home-reveal aria-label="Jalur katalog">
                <div class="home-paths__header">
                    <span>01 / PILIH CAKUPAN</span>
                    <small>Dua jalur, standar yang sama</small>
                </div>
                <a class="home-path" href="{{ route('datasets.index', ['scope' => 'corporate']) }}">
                    <span class="home-path__index">01</span>
                    <span class="home-path__body"><small>PERUSAHAAN & ESG</small><strong>Data untuk riset bisnis dan keberlanjutan</strong><span>Keuangan, emiten, sektor, tata kelola, dan ESG.</span></span>
                    <span class="home-path__arrow" aria-hidden="true">↗</span>
                </a>
                <a class="home-path home-path--regional" href="{{ route('datasets.index', ['scope' => 'regional']) }}">
                    <span class="home-path__index">02</span>
                    <span class="home-path__body"><small>BPS & STATISTIK WILAYAH</small><strong>Indikator untuk riset wilayah</strong><span>Penduduk, ekonomi, provinsi, dan kabupaten/kota.</span></span>
                    <span class="home-path__arrow" aria-hidden="true">↗</span>
                </a>
                <p class="home-paths__foot">Pilih jalur untuk membuka katalog yang sudah terfilter.</p>
            </div>
        </div>
        <div class="container-wide home-hero__foot" aria-label="Standar informasi">
            <span>Definisi variabel</span><span>Sumber tercatat</span><span>Periode jelas</span><span>Akses transparan</span>
        </div>
    </section>

    <section class="home-status" aria-label="Isi katalog saat ini">
        <div class="container-wide home-status__inner">
            <p class="home-status__caption">KATALOG SAAT INI <span>—</span> Data terbit yang dapat ditelusuri</p>
            <div class="home-status__metrics">
                <div><strong data-home-counter="{{ (int) $stats['variables'] }}">{{ number_format($stats['variables'], 0, ',', '.') }}</strong><span>variabel</span></div>
                <div><strong data-home-counter="{{ (int) $stats['observations'] }}">{{ number_format($stats['observations'], 0, ',', '.') }}</strong><span>observasi ditinjau</span></div>
                <div><strong data-home-counter="{{ (int) $stats['providers'] }}">{{ number_format($stats['providers'], 0, ',', '.') }}</strong><span>sumber aktif</span></div>
            </div>
        </div>
    </section>

    <section class="home-section home-featured" aria-labelledby="home-featured-heading">
        <div class="container-wide">
            <div class="home-section__heading" data-home-reveal>
                <div><span class="home-label">LANGSUNG JELAJAHI</span><h2 id="home-featured-heading">Mulai dari variabel,<br>bukan dari nama file.</h2></div>
                <div class="home-section__side"><p>Cek data yang tersedia beserta cakupan dan sumbernya sebelum memutuskan untuk mengunduh atau meminta akses.</p><a href="{{ route('datasets.index') }}">Buka katalog lengkap <span aria-hidden="true">↗</span></a></div>
            </div>
            @if($featuredVariables->isEmpty())
                <div class="home-empty" data-home-reveal>
                    <span class="home-empty__mark" aria-hidden="true">—</span>
                    <div><strong>Belum ada variabel terbit.</strong><p>Katalog akan menampilkan variabel setelah data ditinjau dan dipublikasikan. Kamu tetap bisa melihat halaman katalog.</p></div>
                    <a href="{{ route('datasets.index') }}">Buka katalog <span aria-hidden="true">→</span></a>
                </div>
            @else
                <div class="home-featured__list variable-list variable-list--featured" data-home-reveal>
                    @foreach($featuredVariables as $variable)
                        @include('partials.variable-row', ['variable' => $variable])
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <section class="home-section home-how" id="cara-kerja" aria-labelledby="home-how-heading">
        <div class="container-wide home-how__grid">
            <div data-home-reveal>
                <span class="home-label">CARA KERJA</span>
                <h2 id="home-how-heading">Tiga langkah<br>menuju data.</h2>
                <p class="home-how__intro">Tidak perlu memahami struktur dataset di balik layar. Mulai saja dari indikator yang kamu butuhkan.</p>
                <a class="home-text-link" href="{{ route('datasets.index') }}">Mulai cari variabel <span aria-hidden="true">→</span></a>
            </div>
            <ol class="home-steps" data-home-reveal>
                <li><span>01</span><div><h3>Cari variabel</h3><p>Tulis indikator atau jelajahi katalog Perusahaan & ESG maupun BPS & statistik wilayah.</p></div></li>
                <li><span>02</span><div><h3>Periksa kecocokannya</h3><p>Lihat definisi, satuan, entitas atau wilayah, periode, status kualitas, dan sumber.</p></div></li>
                <li><span>03</span><div><h3>Dapatkan datanya</h3><p>Unduh data terbuka; untuk data terbatas, ajukan permintaan agar pengelola meninjau aksesmu.</p></div></li>
            </ol>
        </div>
    </section>

    <section class="home-section home-standards" id="standar-data" aria-labelledby="home-standards-heading">
        <div class="container-wide">
            <div class="home-section__heading" data-home-reveal>
                <div><span class="home-label">KENAPA BACA DULU?</span><h2 id="home-standards-heading">Angka saja belum cukup.</h2></div>
                <p>Data penelitian yang berguna harus bisa diperiksa asal, arti, dan batas penggunaannya.</p>
            </div>
            <div class="home-standards__grid">
                <article data-home-reveal><span>01 / DEFINISI</span><h3>Tahu apa yang diukur</h3><p>Setiap variabel disertai satuan dan keterangan agar angka tidak kehilangan konteksnya.</p></article>
                <article data-home-reveal><span>02 / SUMBER</span><h3>Bisa telusuri asalnya</h3><p>Koleksi dan penyedia data ditampilkan, termasuk cakupan entitas dan periode.</p></article>
                <article data-home-reveal><span>03 / AKSES</span><h3>Aturannya jelas</h3><p>Data terbuka dapat digunakan langsung; akses terbatas melalui persetujuan pengelola.</p></article>
            </div>
        </div>
    </section>

    <section class="home-outro" aria-labelledby="home-outro-heading">
        <div class="container-wide home-outro__inner" data-home-reveal>
            <div><span class="home-label">SIAP MULAI?</span><h2 id="home-outro-heading">Pertanyaan riset yang baik<br>butuh data yang bisa ditelusuri.</h2></div>
            <div class="home-outro__actions">
                <a class="home-outro__primary" href="{{ route('datasets.index') }}">Jelajahi katalog <span aria-hidden="true">↗</span></a>
                @if(auth('web')->check() && auth('web')->user()->isResearcher())
                    <a class="home-outro__secondary" href="{{ route('user.profile') }}">Profil saya <span aria-hidden="true">→</span></a>
                @else
                    <a class="home-outro__secondary" href="{{ route('register') }}">Buat akun peneliti <span aria-hidden="true">→</span></a>
                @endif
            </div>
        </div>
    </section>
</div>
@endsection
