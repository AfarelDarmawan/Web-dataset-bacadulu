<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ trim($__env->yieldContent('title', 'Admin')) }} — BacaDulu Dataset</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/bacadulu-logo.png') }}">
    <link rel="stylesheet" href="{{ asset('assets/bacadulu-dataset.css') }}?v=7.6.0">
    <link rel="stylesheet" href="{{ asset('assets/bacadulu-admin.css') }}?v=1.3.2">
    @if(request()->routeIs('admin.dashboard'))
        <link rel="stylesheet" href="{{ asset('assets/bacadulu-admin-dashboard.css') }}?v=1.0.0">
    @endif
    @if(request()->routeIs('admin.datasets.index'))
        <link rel="stylesheet" href="{{ asset('assets/bacadulu-admin-collections.css') }}?v=1.0.0">
    @endif
    <script src="{{ asset('assets/gsap.min.js') }}?v=3.13.0" defer></script>
    <script src="{{ asset('assets/bacadulu-dataset.js') }}?v=7.6.4" defer></script>
</head>
@php
    $datasetContext = request()->route('dataset');
    $datasetContext = $datasetContext instanceof \App\Models\Dataset ? $datasetContext : null;
    $connectorContext = request()->route('connector');
    if (!$datasetContext && $connectorContext instanceof \App\Models\DataConnector) {
        $datasetContext = $connectorContext->dataset;
    }
    $documentContext = request()->route('sourceDocument');
    if (!$datasetContext && $documentContext instanceof \App\Models\SourceDocument) {
        $datasetContext = $documentContext->dataset;
    }
    $extractionContext = request()->route('aiExtraction');
    if (!$datasetContext && $extractionContext instanceof \App\Models\AiExtractionJob) {
        $datasetContext = $extractionContext->dataset;
    }
    if (!$datasetContext && request()->routeIs('admin.automation.index', 'admin.automation.connectors.create', 'admin.ai.index')) {
        $datasetId = filter_var(request()->query('dataset'), FILTER_VALIDATE_INT);
        if ($datasetId && $datasetId > 0) {
            $datasetContext = \App\Models\Dataset::find($datasetId);
        }
    }
    $collectionSection = request()->routeIs('admin.datasets.*', 'admin.catalog.*');
    $adminName = auth('admin')->user()->name;
    $adminInitials = collect(array_slice(preg_split('/\s+/', trim($adminName), -1, PREG_SPLIT_NO_EMPTY), 0, 2))
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
    $nextStep = session('admin_next_step');
@endphp
<body class="app-shell app-shell--admin {{ $datasetContext ? 'has-dataset-context' : '' }}">
    <a class="skip-link" href="#admin-content">Lewati ke konten</a>
    <svg class="admin-icon-sprite" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
        <symbol id="ad-home" viewBox="0 0 24 24"><path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1zM9 21v-7h6v7"/></symbol>
        <symbol id="ad-layers" viewBox="0 0 24 24"><rect x="3" y="4" width="7" height="7" rx="1"/><rect x="14" y="4" width="7" height="7" rx="1"/><rect x="3" y="15" width="7" height="7" rx="1"/><rect x="14" y="15" width="7" height="7" rx="1"/></symbol>
        <symbol id="ad-check" viewBox="0 0 24 24"><path d="M12 3 3 7v5c0 6 4 9 9 10 5-1 9-4 9-10V7zM8 12l3 3 5-6"/></symbol>
        <symbol id="ad-inbox" viewBox="0 0 24 24"><path d="M4 4h16l2 11v5H2v-5zM2 15h6l2 3h4l2-3h6"/></symbol>
        <symbol id="ad-source" viewBox="0 0 24 24"><path d="M4 4h16v16H4zM8 8h8M8 12h8M8 16h5"/></symbol>
        <symbol id="ad-sync" viewBox="0 0 24 24"><path d="M20 7a9 9 0 0 0-15-1L3 8m0-5v5h5M4 17a9 9 0 0 0 15 1l2-2m0 5v-5h-5"/></symbol>
        <symbol id="ad-file" viewBox="0 0 24 24"><path d="M5 2h9l5 5v15H5zM14 2v5h5M8 12h8M8 16h6"/></symbol>
        <symbol id="ad-users" viewBox="0 0 24 24"><circle cx="9" cy="8" r="3"/><path d="M3 21v-2a6 6 0 0 1 12 0v2zM17 5a3 3 0 0 1 0 6m1 4a5 5 0 0 1 3 5v1"/></symbol>
        <symbol id="ad-history" viewBox="0 0 24 24"><path d="M3 11a9 9 0 1 1 2 7M3 4v7h7M12 7v5l4 2"/></symbol>
        <symbol id="ad-search" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m16 16 5 5"/></symbol>
        <symbol id="ad-logout" viewBox="0 0 24 24"><path d="M10 3H4v18h6M14 7l5 5-5 5M8 12h11"/></symbol>
        <symbol id="ad-collapse" viewBox="0 0 24 24"><path d="M3 4h18M3 12h18M3 20h18m-9-11-3 3 3 3"/></symbol>
        <symbol id="ad-menu" viewBox="0 0 24 24"><path d="M4 7h16M4 12h16M4 17h16"/></symbol>
        <symbol id="ad-close" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18"/></symbol>
    </svg>
    <aside class="app-sidebar app-sidebar--admin" data-sidebar id="admin-sidebar">
        <div class="app-sidebar__head">
            <a class="brand" href="{{ route('admin.dashboard') }}" aria-label="BacaDulu Dataset - Beranda admin">
                <span class="brand__logo-window" aria-hidden="true"><img class="brand__logo" src="{{ asset('assets/bacadulu-logo.png') }}" alt="" width="447" height="447"></span>
                <span class="brand__product">Dataset</span>
            </a>
            <button class="sidebar-close" type="button" data-sidebar-close aria-label="Tutup menu admin">
                <svg class="admin-nav-icon" aria-hidden="true"><use href="#ad-close"/></svg>
                <span class="sr-only">Tutup menu admin</span>
            </button>
        </div>
        <div class="admin-sidebar-account">
            <span class="admin-sidebar-account__avatar" aria-hidden="true">{{ $adminInitials ?: 'A' }}</span>
            <span class="admin-sidebar-account__details"><strong>{{ $adminName }}</strong><small>Administrator</small></span>
        </div>
        <form class="admin-sidebar-search" method="GET" action="{{ route('admin.datasets.index') }}" role="search" data-admin-search>
            <input data-admin-search-input type="search" name="q" placeholder="Cari koleksi" aria-label="Cari koleksi data" value="{{ request()->routeIs('admin.datasets.index') ? request('q') : '' }}" required>
            <button class="admin-sidebar-search__submit" type="submit" aria-label="Cari koleksi" title="Cari koleksi">
                <svg class="admin-nav-icon" aria-hidden="true"><use href="#ad-search"/></svg>
            </button>
        </form>
        <nav class="app-nav app-nav--admin" aria-label="Menu utama admin">
            <span class="app-nav__label">Ruang kerja</span>
            <a title="Beranda admin" class="{{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}" href="{{ route('admin.dashboard') }}" @if(request()->routeIs('admin.dashboard')) aria-current="page" @endif><svg class="admin-nav-icon" aria-hidden="true"><use href="#ad-home"/></svg><span class="admin-nav-text">Beranda admin</span></a>
            <a data-admin-nav="datasets" title="Koleksi data" class="{{ $collectionSection ? 'is-active' : '' }}" href="{{ route('admin.datasets.index') }}" @if($collectionSection) aria-current="page" @endif><svg class="admin-nav-icon" aria-hidden="true"><use href="#ad-layers"/></svg><span class="admin-nav-text">Koleksi data</span></a>
            <a data-admin-nav="quality" title="Perlu ditinjau" class="{{ request()->routeIs('admin.quality.*') ? 'is-active' : '' }}" href="{{ route('admin.quality.index') }}" @if(request()->routeIs('admin.quality.*')) aria-current="page" @endif><svg class="admin-nav-icon" aria-hidden="true"><use href="#ad-check"/></svg><span class="admin-nav-text">Perlu ditinjau</span></a>
            <a title="Permintaan akses" class="{{ request()->routeIs('admin.requests.*') ? 'is-active' : '' }}" href="{{ route('admin.requests.index') }}" @if(request()->routeIs('admin.requests.*')) aria-current="page" @endif><svg class="admin-nav-icon" aria-hidden="true"><use href="#ad-inbox"/></svg><span class="admin-nav-text">Permintaan akses</span></a>
            <span class="app-nav__label admin-nav-section-label">Isi data</span>
            <a title="Sumber data" class="{{ request()->routeIs('admin.providers.*') ? 'is-active' : '' }}" href="{{ route('admin.providers.index') }}" @if(request()->routeIs('admin.providers.*')) aria-current="page" @endif><svg class="admin-nav-icon" aria-hidden="true"><use href="#ad-source"/></svg><span class="admin-nav-text">Sumber data</span></a>
            <a data-admin-nav="automation" title="Sinkronisasi BPS" class="{{ request()->routeIs('admin.automation.*') ? 'is-active' : '' }}" href="{{ route('admin.automation.index') }}" @if(request()->routeIs('admin.automation.*')) aria-current="page" @endif><svg class="admin-nav-icon" aria-hidden="true"><use href="#ad-sync"/></svg><span class="admin-nav-text">Sinkronisasi BPS</span></a>
            <a title="Dokumen &amp; ekstraksi" class="{{ request()->routeIs('admin.ai.*', 'admin.source-documents.*', 'admin.ai-extractions.*') ? 'is-active' : '' }}" href="{{ route('admin.ai.index') }}" @if(request()->routeIs('admin.ai.*', 'admin.source-documents.*', 'admin.ai-extractions.*')) aria-current="page" @endif><svg class="admin-nav-icon" aria-hidden="true"><use href="#ad-file"/></svg><span class="admin-nav-text">Dokumen &amp; ekstraksi</span></a>
            <span class="app-nav__label admin-nav-section-label">Akun &amp; sistem</span>
            <a title="Pengguna" class="{{ request()->routeIs('admin.users.*') ? 'is-active' : '' }}" href="{{ route('admin.users.index') }}" @if(request()->routeIs('admin.users.*')) aria-current="page" @endif><svg class="admin-nav-icon" aria-hidden="true"><use href="#ad-users"/></svg><span class="admin-nav-text">Pengguna</span></a>
            <a title="Audit log" class="{{ request()->routeIs('admin.audits.*') ? 'is-active' : '' }}" href="{{ route('admin.audits.index') }}" @if(request()->routeIs('admin.audits.*')) aria-current="page" @endif><svg class="admin-nav-icon" aria-hidden="true"><use href="#ad-history"/></svg><span class="admin-nav-text">Audit log</span></a>
        </nav>
        <div class="app-sidebar__foot">
            <button type="button" class="admin-sidebar-collapse" data-admin-collapse aria-label="Ringkas sidebar" aria-expanded="true" title="Ringkas sidebar"><svg class="admin-nav-icon" aria-hidden="true"><use href="#ad-collapse"/></svg><span class="admin-nav-text">Ringkas menu</span></button>
            <form method="POST" action="{{ route('admin.logout') }}">@csrf<button type="submit" title="Keluar dari admin"><svg class="admin-nav-icon" aria-hidden="true"><use href="#ad-logout"/></svg><span class="admin-nav-text">Keluar</span></button></form>
        </div>
    </aside>
    <div class="app-main">
        <header class="app-topbar">
            <button class="sidebar-open" type="button" data-sidebar-open aria-label="Buka menu admin" aria-controls="admin-sidebar" aria-expanded="false">
                <svg class="admin-nav-icon" aria-hidden="true"><use href="#ad-menu"/></svg>
                <span class="sr-only">Buka menu admin</span>
            </button>
            <nav class="admin-breadcrumb" aria-label="Posisi halaman">
                <a href="{{ route('admin.dashboard') }}">Admin</a>
                <span aria-hidden="true">/</span>
                @if($datasetContext)
                    <a href="{{ route('admin.datasets.index') }}">Koleksi</a>
                    <span aria-hidden="true">/</span>
                    <strong title="{{ $datasetContext->title }}">{{ $datasetContext->title }}</strong>
                @else
                    <strong>{{ trim($__env->yieldContent('page_title', 'Beranda')) }}</strong>
                @endif
            </nav>
            <a class="button button--line button--small" href="{{ route('home') }}" target="_blank" rel="noopener">Lihat situs</a>
        </header>
        <main class="app-content" id="admin-content">
            @include('partials.flash')
            @if($datasetContext)
                @include('admin.partials.collection-steps', ['currentDataset' => $datasetContext])
            @endif
            @yield('content')
        </main>
    </div>
    <div class="sidebar-backdrop" data-sidebar-backdrop></div>
    @if(is_array($nextStep) && !empty($nextStep['url']) && !empty($nextStep['title']))
        <dialog class="admin-next-dialog" data-admin-next-step data-admin-next-nav="{{ $nextStep['nav'] ?? '' }}" aria-labelledby="admin-next-title" aria-describedby="admin-next-description">
            <div class="admin-next-dialog__card">
                <button type="button" class="admin-next-dialog__close" data-admin-next-close aria-label="Tutup arahan">&times;</button>
                <span class="admin-next-dialog__eyebrow">Langkah berikutnya</span>
                <h2 id="admin-next-title">{{ $nextStep['title'] }}</h2>
                <p id="admin-next-description">{{ $nextStep['description'] ?? '' }}</p>
                <div class="admin-next-dialog__actions">
                    <a class="button button--ink" href="{{ $nextStep['url'] }}">{{ $nextStep['label'] ?? 'Lanjut' }} <span aria-hidden="true">→</span></a>
                    <button type="button" data-admin-next-close>Lewati dulu</button>
                </div>
            </div>
        </dialog>
    @endif
</body>
</html>
