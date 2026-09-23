@extends('layouts.public')

@section('title', 'Katalog Variabel — BacaDulu Dataset')

@section('content')
@php
    $filterKeys = ['q', 'scope', 'category', 'provider', 'period', 'entity', 'access'];
    $activeFilterCount = collect($filterKeys)->filter(fn ($key) => request()->filled($key))->count();
    $accessNames = ['open' => 'Terbuka', 'restricted' => 'Terbatas', 'commercial' => 'Berlisensi'];
    $scopeNames = ['corporate' => 'Perusahaan & ESG', 'regional' => 'Statistik wilayah'];
    $selectedProvider = $providers->firstWhere('id', (int) request('provider'));
    $activeFilters = collect([
        'q' => request('q') ? 'Kata kunci · '.request('q') : null,
        'scope' => request('scope') ? ($scopeNames[request('scope')] ?? request('scope')) : null,
        'category' => request('category') ? 'Topik · '.request('category') : null,
        'provider' => request('provider') ? 'Sumber · '.($selectedProvider?->name ?? request('provider')) : null,
        'period' => request('period') ? 'Periode · '.request('period') : null,
        'entity' => request('entity') ? 'Entitas · '.request('entity') : null,
        'access' => request('access') ? 'Akses · '.($accessNames[request('access')] ?? request('access')) : null,
    ])->filter();
@endphp
<section class="catalog-hero catalog-hero--variables">
    <div class="container-wide">
        <span class="eyebrow">Katalog variabel</span>
        <div class="catalog-hero__title">
            <h1>Temukan variabel.<br>Susun data risetmu.</h1>
            <p>Cari indikator perusahaan, ESG, ekonomi, sosial, dan statistik wilayah. Hasil ditampilkan pada level variabel agar definisi, entitas, periode, serta sumber dapat diperiksa sebelum data dipilih.</p>
        </div>

        <nav class="scope-switch" aria-label="Cakupan data">
            <a class="scope-switch__item {{ request('scope') ? '' : 'is-active' }}" href="{{ route('datasets.index', request()->except('scope', 'page')) }}">
                <span>Semua data</span><small>Seluruh variabel terbit</small>
            </a>
            <a class="scope-switch__item {{ request('scope') === 'corporate' ? 'is-active' : '' }}" href="{{ route('datasets.index', array_merge(request()->except('scope', 'page'), ['scope' => 'corporate'])) }}">
                <span>Perusahaan & ESG</span><small>Emiten, keuangan, keberlanjutan</small>
            </a>
            <a class="scope-switch__item {{ request('scope') === 'regional' ? 'is-active' : '' }}" href="{{ route('datasets.index', array_merge(request()->except('scope', 'page'), ['scope' => 'regional'])) }}">
                <span>Statistik wilayah</span><small>BPS, kementerian, pemerintah</small>
            </a>
        </nav>

        <button class="catalog-filter-toggle" type="button" aria-controls="catalog-filter-panel" aria-expanded="false" data-filter-toggle data-start-open="{{ $activeFilterCount > 0 ? 'true' : 'false' }}">
            <span>Filter katalog</span><b>{{ $activeFilterCount > 0 ? $activeFilterCount.' aktif' : 'Buka filter' }}</b>
        </button>
        <form id="catalog-filter-panel" class="catalog-filter catalog-filter--variables" method="GET" action="{{ route('datasets.index') }}" data-filter-panel>
            @if(request('scope'))<input type="hidden" name="scope" value="{{ request('scope') }}">@endif
            <div class="field field--search field--wide"><label for="q">Cari variabel</label><input id="q" name="q" type="search" value="{{ request('q') }}" placeholder="Contoh: ROA, emisi scope 1, jumlah penduduk" maxlength="100" data-catalog-search></div>
            <div class="field"><label for="category">Topik</label><select id="category" name="category"><option value="">Semua topik</option>@foreach ($categories as $category)<option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>@endforeach</select></div>
            <div class="field"><label for="provider">Sumber</label><select id="provider" name="provider"><option value="">Semua sumber</option>@foreach ($providers as $provider)<option value="{{ $provider->id }}" @selected((string) request('provider') === (string) $provider->id)>{{ $provider->name }}</option>@endforeach</select></div>
            <div class="field"><label for="period">Periode</label><select id="period" name="period"><option value="">Semua periode</option>@foreach ($periods as $period)<option value="{{ $period }}" @selected(request('period') === $period)>{{ $period }}</option>@endforeach</select></div>
            <div class="field"><label for="entity">Perusahaan atau wilayah</label><input id="entity" name="entity" value="{{ request('entity') }}" maxlength="100" placeholder="BBCA, Jawa Barat, Indonesia"></div>
            <div class="field"><label for="access">Akses</label><select id="access" name="access"><option value="">Semua akses</option><option value="open" @selected(request('access') === 'open')>Terbuka</option><option value="restricted" @selected(request('access') === 'restricted')>Terbatas</option><option value="commercial" @selected(request('access') === 'commercial')>Berlisensi</option></select></div>
            <button class="button button--ink" type="submit">Terapkan filter</button>
        </form>
        @if($activeFilters->isNotEmpty())
            <div class="catalog-active-filters" aria-label="Filter yang sedang digunakan">
                <span>Filter aktif</span>
                @foreach($activeFilters as $key => $label)
                    <a href="{{ route('datasets.index', request()->except($key, 'page')) }}" aria-label="Hapus filter {{ $label }}">{{ $label }} <b aria-hidden="true">×</b></a>
                @endforeach
                <a class="catalog-active-filters__reset" href="{{ route('datasets.index') }}">Reset semua</a>
            </div>
        @endif
    </div>
</section>

<section class="section section--catalog">
    <div class="container-wide">
        <div class="result-line">
            <strong>{{ number_format($variables->total()) }} variabel</strong>
            <span>{{ request()->hasAny(['q','scope','category','provider','period','entity','access']) ? 'sesuai pencarian' : 'siap ditelusuri' }}</span>
            @if(request()->hasAny(['q','scope','category','provider','period','entity','access']))<a href="{{ route('datasets.index') }}">Hapus semua filter</a>@endif
        </div>
        @if ($variables->isEmpty())
            <div class="public-empty"><span>TIDAK ADA HASIL</span><h3>Belum ada variabel yang cocok.</h3><p>Coba nama indikator lain, hapus satu filter, atau periksa jalur Perusahaan & ESG dan Statistik wilayah.</p><a class="button button--line" href="{{ route('datasets.index') }}">Hapus semua filter</a></div>
        @else
            <div class="variable-list">
                <div class="variable-list__head" aria-hidden="true"><span>Variabel dan sumber</span><span>Cakupan</span><span>Ketersediaan</span><span>Akses</span><span></span></div>
                @foreach ($variables as $variable)
                    @include('partials.variable-row', ['variable' => $variable])
                @endforeach
            </div>
            <div class="pagination-wrap">{{ $variables->links() }}</div>
        @endif
    </div>
</section>
@endsection
