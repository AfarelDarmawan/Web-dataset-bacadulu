<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ $title ?? 'Terjadi kendala' }} — BacaDulu Dataset</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/bacadulu-logo.png') }}">
    <link rel="stylesheet" href="{{ asset('assets/bacadulu-dataset.css') }}?v=7.6.0">
</head>
<body class="error-shell">
    <main class="error-card">
        @include('partials.brand')
        <span class="error-code">{{ $code ?? '—' }}</span>
        <h1>{{ $title ?? 'Halaman belum dapat ditampilkan.' }}</h1>
        <p>{{ $message ?? 'Coba muat ulang halaman atau kembali ke katalog.' }}</p>
        <div class="error-actions"><a class="button button--ink" href="{{ route('home') }}">Kembali ke beranda</a><a class="button button--line" href="{{ route('datasets.index') }}">Buka katalog</a></div>
    </main>
</body>
</html>
