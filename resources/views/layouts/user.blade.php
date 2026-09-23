<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ trim($__env->yieldContent('title', 'Profil peneliti')) }} — BacaDulu Dataset</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/bacadulu-logo.png') }}">
    <link rel="stylesheet" href="{{ asset('assets/bacadulu-dataset.css') }}?v=7.6.0">
    <script src="{{ asset('assets/gsap.min.js') }}?v=3.13.0" defer></script>
    <script src="{{ asset('assets/bacadulu-dataset.js') }}?v=7.6.0" defer></script>
</head>
<body class="app-shell">
    <aside class="app-sidebar" data-sidebar>
        <div class="app-sidebar__head">
            @include('partials.brand', ['class' => 'brand--light'])
            <button class="sidebar-close" type="button" data-sidebar-close aria-label="Tutup menu">&times;</button>
        </div>
        <nav class="app-nav" aria-label="Menu pengguna">
            <span class="app-nav__label">Akun peneliti</span>
            <a class="{{ request()->routeIs('user.profile') ? 'is-active' : '' }}" href="{{ route('user.profile') }}"><span>01</span>Profil saya</a>
            <a class="{{ request()->routeIs('datasets.*') ? 'is-active' : '' }}" href="{{ route('datasets.index') }}"><span>02</span>Katalog variabel</a>
            <a class="{{ request()->routeIs('user.requests.*') ? 'is-active' : '' }}" href="{{ route('user.requests.index') }}"><span>03</span>Permintaan akses</a>
        </nav>
        <div class="app-sidebar__foot">
            <p>{{ auth('web')->user()->name }}</p>
            <small>{{ auth('web')->user()->institution ?: auth('web')->user()->email }}</small>
            <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Keluar</button></form>
        </div>
    </aside>
    <div class="app-main">
        <header class="app-topbar">
            <button class="sidebar-open" type="button" data-sidebar-open aria-label="Buka menu">Menu</button>
            <div>
                <span class="app-topbar__context">Akun peneliti</span>
                <strong>{{ trim($__env->yieldContent('page_title', 'Profil')) }}</strong>
            </div>
            <a class="button button--line button--small" href="{{ route('datasets.index') }}">Jelajahi katalog</a>
        </header>
        <main class="app-content">
            @include('partials.flash')
            @yield('content')
        </main>
    </div>
    <div class="sidebar-backdrop" data-sidebar-backdrop></div>
</body>
</html>
