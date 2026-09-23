<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <title>{{ trim($__env->yieldContent('title', 'Administrasi')) }} — BacaDulu Dataset</title>

    <link rel="icon" type="image/png" href="{{ asset('assets/bacadulu-logo.png') }}">
    <link rel="stylesheet" href="{{ asset('assets/bacadulu-dataset.css') }}?v=7.6.0">
    <link rel="stylesheet" href="{{ asset('assets/bacadulu-admin-auth.css') }}?v=1.0.0">

    <script src="{{ asset('assets/gsap.min.js') }}?v=3.13.0" defer></script>
    <script src="{{ asset('assets/bacadulu-admin-auth.js') }}?v=1.0.0" defer></script>
</head>
<body class="admin-auth-shell">
    <a class="skip-link" href="#admin-login">Lewati ke formulir masuk</a>

    <main class="admin-auth-frame">
        <section class="admin-auth-context" aria-label="Tentang panel administrasi">
            <div class="admin-auth-context__top">
                @include('partials.brand', ['class' => 'brand--light'])
                <span class="admin-auth-context__label">Ruang pengelola</span>
            </div>

            <div class="admin-auth-context__body">
                <span class="admin-auth-kicker">BacaDulu / Dataset</span>
                <h1>Data yang rapi<br>dimulai di sini.</h1>
                <p>Kelola sumber BPS dan perusahaan, periksa setiap perubahan, lalu terbitkan data yang siap dipakai peneliti.</p>

                <ol class="admin-auth-steps" aria-label="Alur pengelolaan data">
                    <li><span>01</span><strong>Hubungkan sumber</strong></li>
                    <li><span>02</span><strong>Periksa data</strong></li>
                    <li><span>03</span><strong>Terbitkan katalog</strong></li>
                </ol>
            </div>

            <div class="admin-auth-context__bottom">
                <span class="admin-auth-bars" aria-hidden="true">
                    <i></i><i></i><i></i>
                </span>
                <span>Administrasi data penelitian</span>
            </div>
        </section>

        <section class="admin-auth-panel" id="admin-login" aria-label="Masuk administrator">
            <div class="admin-auth-panel__inner">
                <div class="admin-auth-panel__top">
                    <span>Panel administrator</span>
                    <span class="admin-auth-panel__index" aria-hidden="true">01 / 01</span>
                </div>

                @include('partials.flash')
                @yield('content')

                <div class="admin-auth-panel__foot">
                    <span>Akun peneliti menggunakan halaman masuk yang berbeda.</span>
                    <a href="{{ route('home') }}">
                        Kembali ke situs <span aria-hidden="true">↗</span>
                    </a>
                </div>
            </div>
        </section>
    </main>
</body>
</html>