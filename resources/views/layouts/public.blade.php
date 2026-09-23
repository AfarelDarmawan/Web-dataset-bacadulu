<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="{{ trim($__env->yieldContent('meta_description', 'BacaDulu Dataset adalah katalog data penelitian yang terstruktur, tertelusur, dan siap digunakan.')) }}">
    <title>{{ trim($__env->yieldContent('title', 'BacaDulu Dataset')) }}</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/bacadulu-logo.png') }}">
    <link rel="stylesheet" href="{{ asset('assets/bacadulu-dataset.css') }}?v=7.6.3">
    <link rel="stylesheet" href="{{ asset('assets/bacadulu-brand.css') }}?v=1.2.0">
    @stack('page_styles')
    <script src="{{ asset('assets/gsap.min.js') }}?v=3.13.0" defer></script>
    <script src="{{ asset('assets/bacadulu-dataset.js') }}?v=7.6.3" defer></script>
    @stack('page_scripts')
</head>
<body class="public-shell">
    <a class="skip-link" href="#content">Lewati ke konten</a>
    <header class="site-header" data-site-header>
        <div class="site-header__inner container-wide">
            @include('partials.brand')

            <button class="nav-toggle" type="button" data-nav-toggle aria-expanded="false" aria-controls="site-navigation">
                <span></span><span></span><span></span>
                <span class="sr-only">Buka navigasi</span>
            </button>

            <nav id="site-navigation" class="site-nav" data-nav-menu aria-label="Navigasi utama">
                <a class="{{ request()->routeIs('home') ? 'is-active' : '' }}" href="{{ route('home') }}">Beranda</a>
                <a class="{{ request()->routeIs('datasets.*') ? 'is-active' : '' }}" href="{{ route('datasets.index') }}">Katalog variabel</a>
                <a href="{{ route('home') }}#cara-kerja">Cara kerja</a>
                <a href="{{ route('home') }}#standar-data">Standar data</a>
                @if(auth('web')->check() && auth('web')->user()->isResearcher())
                    <a class="site-nav__mobile-action" href="{{ route('user.profile') }}">Profil peneliti</a>
                @else
                    <a class="site-nav__mobile-action" href="{{ route('login') }}">Masuk</a>
                    <a class="site-nav__mobile-action" href="{{ route('register') }}">Buat akun</a>
                @endif
            </nav>

            <div class="site-header__actions">
                @if(auth('web')->check() && auth('web')->user()->isResearcher())
                    @php
                        $headerUser = auth('web')->user();
                        $headerNameParts = preg_split('/\s+/', trim($headerUser->name), -1, PREG_SPLIT_NO_EMPTY);
                        $headerInitials = collect(array_slice($headerNameParts, 0, 2))->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
                        $headerAvatar = trim((string) $headerUser->avatar);
                        $headerAvatarUrl = null;

                        if ($headerAvatar !== '') {
                            if (str_starts_with($headerAvatar, 'http://') || str_starts_with($headerAvatar, 'https://')) {
                                $headerAvatarUrl = $headerAvatar;
                            } else {
                                $headerAvatarPath = preg_replace('#^(storage/|public/)#i', '', ltrim($headerAvatar, '/'));
                                $headerAvatarDisk = \Illuminate\Support\Facades\Storage::disk('public');

                                if ($headerAvatarDisk->exists($headerAvatarPath)) {
                                    $headerAvatarUrl = route('media.avatar', ['filename' => basename($headerAvatarPath)]);
                                }
                            }
                        }
                    @endphp
                    <div class="account-menu" data-account-menu>
                        <button class="account-menu__trigger" type="button" data-account-menu-toggle aria-expanded="false" aria-controls="account-menu-panel">
                            <span class="account-menu__avatar" aria-hidden="true">
                                @if($headerAvatarUrl)
                                    <img src="{{ $headerAvatarUrl }}" alt="">
                                @else
                                    {{ $headerInitials ?: 'P' }}
                                @endif
                            </span>
                            <span class="sr-only">Buka menu akun</span>
                        </button>
                        <div id="account-menu-panel" class="account-menu__panel" data-account-menu-panel hidden>
                            <strong>{{ $headerUser->name }}</strong>
                            <small>{{ $headerUser->email }}</small>
                            <a href="{{ route('user.profile') }}">Profil saya</a>
                            <a href="{{ route('user.profile.edit') }}">Edit profil</a>
                            <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Keluar</button></form>
                        </div>
                    </div>
                @else
                    <a class="text-link" href="{{ route('login') }}">Masuk</a>
                    <a class="button button--ink button--small" href="{{ route('register') }}">Buat akun</a>
                @endif
            </div>
        </div>
    </header>

    <main id="content">
        @if(session('success') || session('error') || $errors->any())
            <div class="container-wide public-flash">@include('partials.flash')</div>
        @endif
        @yield('content')
    </main>

    @include('partials.public-footer')
</body>
</html>
