<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ trim($__env->yieldContent('title', 'Akun peneliti')) }} — BacaDulu Dataset</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/bacadulu-logo.png') }}">
    <link rel="stylesheet" href="{{ asset('assets/bacadulu-dataset.css') }}?v=7.6.0">
    <link rel="stylesheet" href="{{ asset('assets/bacadulu-auth.css') }}?v=4.0.0">
    <script src="{{ asset('assets/gsap.min.js') }}?v=3.13.0" defer></script>
    <script src="{{ asset('assets/bacadulu-dataset.js') }}?v=7.6.4" defer></script>
    <script src="{{ asset('assets/bacadulu-auth.js') }}?v=3.8.0" defer></script>
</head>
<body class="auth-shell">
    <a class="skip-link" href="#content">Lewati ke formulir</a>
    <div class="auth-page">
        <header class="auth-page__header">
            <a href="{{ route('home') }}">← Kembali ke situs</a>
        </header>
        <main id="content" class="auth-page__main">
            @include('partials.flash')
            @yield('content')
        </main>
        <footer class="auth-page__footer">
            <span>&copy; {{ date('Y') }} BacaDulu Dataset</span>
            <span>Sumber data jelas, penelitian lebih mudah.</span>
        </footer>
    </div>
</body>
</html>