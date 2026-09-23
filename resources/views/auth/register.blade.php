@extends('layouts.auth')

@section('title', 'Daftar Peneliti')

@section('content')
@php($authIsRegister = true)
<div class="auth-card" data-auth-card data-view="{{ $authIsRegister ? 'signup' : 'signin' }}">
    <nav class="auth-card__nav" aria-label="Akun peneliti">
        <span class="auth-card__active-bar" aria-hidden="true"></span>
        <a href="{{ route('login') }}" data-auth-view="signin" @if(!$authIsRegister) aria-current="page" @endif>
            <span>Masuk</span>
        </a>
        <a href="{{ route('register') }}" data-auth-view="signup" @if($authIsRegister) aria-current="page" @endif>
            <span>Daftar</span>
        </a>
    </nav>

    <div class="auth-card__stage">
        <div class="auth-card__forms" data-auth-forms>
            <div class="auth-card__forms-track">
                <section class="auth-card__form-pane" data-auth-pane="signup" aria-label="Buat akun peneliti" @if(!$authIsRegister) aria-hidden="true" inert @endif>
                    <div class="auth-card__form-inner">
                        <span class="auth-card__eyebrow">AKUN PENELITI</span>
                        <h2>Buat akun.</h2>
                        <p>Mulai cari variabel dan ajukan akses data.</p>
                        <form class="auth-card__form" method="POST" action="{{ route('register.store') }}">
                            @csrf
                            <div class="field"><label for="signup-name">Nama lengkap</label><input id="signup-name" name="name" type="text" value="{{ old('name') }}" autocomplete="name" maxlength="120" required placeholder="Nama sesuai identitas" @if($authIsRegister) autofocus @endif></div>
                            <div class="field"><label for="signup-email">Alamat email</label><input id="signup-email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" maxlength="190" required placeholder="nama@institusi.ac.id"></div>
                            <div class="field"><label for="signup-institution">Institusi <span>(opsional)</span></label><input id="signup-institution" name="institution" type="text" value="{{ old('institution') }}" autocomplete="organization" maxlength="190" placeholder="Universitas atau organisasi"></div>
                            <div class="auth-card__password-row">
                                <div class="field"><label for="signup-password">Kata sandi</label><div class="password-field"><input id="signup-password" name="password" type="password" autocomplete="new-password" minlength="12" maxlength="128" required><button type="button" data-password-toggle data-target="signup-password">Lihat</button></div></div>
                                <div class="field"><label for="signup-password-confirmation">Ulangi sandi</label><div class="password-field"><input id="signup-password-confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="12" maxlength="128" required><button type="button" data-password-toggle data-target="signup-password-confirmation">Lihat</button></div></div>
                            </div>
                            <small class="auth-card__hint">Minimal 12 karakter, huruf besar dan kecil, angka, serta simbol.</small>
                            <label class="check-control"><input type="checkbox" name="terms" value="1" required @checked(old('terms'))><span>Saya akan menggunakan data sesuai lisensi dan etika penelitian.</span></label>
                            <button class="auth-card__submit" type="submit">Buat akun <span aria-hidden="true">→</span></button>
                        </form>
                        <p class="auth-card__other">Sudah punya akun? <a href="{{ route('login') }}" data-auth-view="signin">Masuk di sini</a></p>
                    </div>
                </section>

                <section class="auth-card__form-pane" data-auth-pane="signin" aria-label="Masuk peneliti" @if($authIsRegister) aria-hidden="true" inert @endif>
                    <div class="auth-card__form-inner">
                        <span class="auth-card__eyebrow">AKUN PENELITI</span>
                        <h2>Selamat datang.</h2>
                        <p>Masuk untuk melanjutkan penelitianmu.</p>
                        <form class="auth-card__form" method="POST" action="{{ route('login.store') }}">
                            @csrf
                            <div class="field"><label for="signin-email">Alamat email</label><input id="signin-email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" maxlength="190" required placeholder="nama@institusi.ac.id" @if(!$authIsRegister) autofocus @endif></div>
                            <div class="field"><label for="signin-password">Kata sandi</label><div class="password-field"><input id="signin-password" name="password" type="password" autocomplete="current-password" maxlength="128" required><button type="button" data-password-toggle data-target="signin-password">Lihat</button></div></div>
                            <label class="check-control"><input type="checkbox" name="remember" value="1" @checked(old('remember'))><span>Ingat perangkat ini</span></label>
                            <button class="auth-card__submit" type="submit">Masuk <span aria-hidden="true">→</span></button>
                        </form>
                        <p class="auth-card__other">Belum punya akun? <a href="{{ route('register') }}" data-auth-view="signup">Buat akun</a></p>
                    </div>
                </section>
            </div>
        </div>

        <aside class="auth-card__hero" aria-label="Tentang BacaDulu Dataset">
            <div class="auth-card__hero-track">
                <div class="auth-card__hero-slide" data-auth-hero="signin" @if($authIsRegister) aria-hidden="true" @endif>
                    <div class="auth-card__hero-content">
                        <div class="auth-card__hero-symbol" aria-hidden="true"><span></span><span></span><span></span></div>
                        <div><h1>Selamat datang<br>kembali.</h1><p>Data penelitianmu menunggu di sini.</p></div>
                    </div>
                </div>
                <div class="auth-card__hero-slide" data-auth-hero="signup" @if(!$authIsRegister) aria-hidden="true" @endif>
                    <div class="auth-card__hero-content">
                        <div class="auth-card__hero-symbol" aria-hidden="true"><span></span><span></span><span></span></div>
                        <div><h1>Awal adalah<br>langkah pertama.</h1><p>Jelajahi data yang bisa ditelusuri sumbernya.</p></div>
                    </div>
                </div>
            </div>
        </aside>
        <svg class="auth-card__wave" data-auth-wave viewBox="0 0 1000 600" preserveAspectRatio="none" aria-hidden="true" focusable="false">
            <path data-auth-wave-path d="M -200 0 C -100 150 -250 450 -150 600 L -300 600 L -300 0 Z" fill="#241b52" />
        </svg>
    </div>
</div>
@endsection
