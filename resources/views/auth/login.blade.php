@extends('layouts.auth')

@section('title', 'Masuk Peneliti')

@section('content')
@php($authIsRegister = false)
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
                        <h2>Buat akun</h2>
                        <p>Mulai cari variabel dan ajukan akses data</p>

                        <form class="auth-card__form" method="POST" action="{{ route('register.store') }}">
                            @csrf

                            <div class="field">
                                <label for="signup-name">Nama lengkap</label>
                                <input
                                    id="signup-name"
                                    name="name"
                                    type="text"
                                    value="{{ old('name') }}"
                                    autocomplete="name"
                                    maxlength="120"
                                    required
                                    placeholder="Nama sesuai identitas"
                                    @if($authIsRegister) autofocus @endif
                                >
                            </div>

                            <div class="field">
                                <label for="signup-email">Alamat email</label>
                                <input
                                    id="signup-email"
                                    name="email"
                                    type="email"
                                    value="{{ old('email') }}"
                                    autocomplete="email"
                                    maxlength="190"
                                    required
                                    placeholder="nama@institusi.ac.id"
                                >
                            </div>

                            <div class="field">
                                <label for="signup-institution">Institusi <span>(opsional)</span></label>
                                <input
                                    id="signup-institution"
                                    name="institution"
                                    type="text"
                                    value="{{ old('institution') }}"
                                    autocomplete="organization"
                                    maxlength="190"
                                    placeholder="Universitas atau organisasi"
                                >
                            </div>

                            <div class="auth-card__password-row">
                                <div class="field">
                                    <label for="signup-password">Kata sandi</label>
                                    <div class="password-field">
                                        <input
                                            id="signup-password"
                                            name="password"
                                            type="password"
                                            autocomplete="new-password"
                                            minlength="12"
                                            maxlength="128"
                                            required
                                        >
                                        <button
                                            class="auth-password-toggle"
                                            type="button"
                                            data-auth-password-toggle
                                            data-target="signup-password"
                                            aria-label="Tampilkan kata sandi"
                                            aria-pressed="false"
                                            title="Tampilkan kata sandi"
                                        >
                                            <svg data-password-icon="show" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                                <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/>
                                                <circle cx="12" cy="12" r="3"/>
                                            </svg>
                                            <svg data-password-icon="hide" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                                <path d="m3 3 18 18M10.6 10.6a2 2 0 0 0 2.8 2.8"/>
                                                <path d="M9.9 5.2A10.8 10.8 0 0 1 12 5c6.4 0 10 7 10 7s-3.2 6.2-9.2 7"/>
                                                <path d="M6.2 6.2C3.5 8 2 12 2 12s1 2 3.3 3.8"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <div class="field">
                                    <label for="signup-password-confirmation">Ulangi sandi</label>
                                    <div class="password-field">
                                        <input
                                            id="signup-password-confirmation"
                                            name="password_confirmation"
                                            type="password"
                                            autocomplete="new-password"
                                            minlength="12"
                                            maxlength="128"
                                            required
                                        >
                                        <button
                                            class="auth-password-toggle"
                                            type="button"
                                            data-auth-password-toggle
                                            data-target="signup-password-confirmation"
                                            aria-label="Tampilkan kata sandi"
                                            aria-pressed="false"
                                            title="Tampilkan kata sandi"
                                        >
                                            <svg data-password-icon="show" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                                <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/>
                                                <circle cx="12" cy="12" r="3"/>
                                            </svg>
                                            <svg data-password-icon="hide" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                                <path d="m3 3 18 18M10.6 10.6a2 2 0 0 0 2.8 2.8"/>
                                                <path d="M9.9 5.2A10.8 10.8 0 0 1 12 5c6.4 0 10 7 10 7s-3.2 6.2-9.2 7"/>
                                                <path d="M6.2 6.2C3.5 8 2 12 2 12s1 2 3.3 3.8"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <small class="auth-card__hint">
                                Minimal 12 karakter, huruf besar dan kecil, angka, serta simbol.
                            </small>

                            <label class="check-control">
                                <input type="checkbox" name="terms" value="1" required @checked(old('terms'))>
                                <span>Saya akan menggunakan data sesuai lisensi dan etika penelitian.</span>
                            </label>

                            <button class="auth-card__submit" type="submit">
                                Buat akun <span aria-hidden="true">→</span>
                            </button>
                        </form>

                        <p class="auth-card__other">
                            Sudah punya akun?
                            <a href="{{ route('login') }}" data-auth-view="signin">Masuk di sini</a>
                        </p>
                    </div>
                </section>

                <section class="auth-card__form-pane" data-auth-pane="signin" aria-label="Masuk peneliti" @if($authIsRegister) aria-hidden="true" inert @endif>
                    <div class="auth-card__form-inner">
                        
                        <h2>Selamat datang</h2>
                        <p>Masuk untuk melanjutkan penelitianmu</p>

                        <form class="auth-card__form" method="POST" action="{{ route('login.store') }}">
                            @csrf

                            <div class="field">
                                <label for="signin-email">Alamat email</label>
                                <input
                                    id="signin-email"
                                    name="email"
                                    type="email"
                                    value="{{ old('email') }}"
                                    autocomplete="email"
                                    maxlength="190"
                                    required
                                    placeholder="nama@institusi.ac.id"
                                    @if(!$authIsRegister) autofocus @endif
                                >
                            </div>

                            <div class="field">
                                <label for="signin-password">Kata sandi</label>
                                <div class="password-field">
                                    <input
                                        id="signin-password"
                                        name="password"
                                        type="password"
                                        autocomplete="current-password"
                                        maxlength="128"
                                        required
                                    >
                                    <button
                                        class="auth-password-toggle"
                                        type="button"
                                        data-auth-password-toggle
                                        data-target="signin-password"
                                        aria-label="Tampilkan kata sandi"
                                        aria-pressed="false"
                                        title="Tampilkan kata sandi"
                                    >
                                        <svg data-password-icon="show" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                            <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                        <svg data-password-icon="hide" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                            <path d="m3 3 18 18M10.6 10.6a2 2 0 0 0 2.8 2.8"/>
                                            <path d="M9.9 5.2A10.8 10.8 0 0 1 12 5c6.4 0 10 7 10 7s-3.2 6.2-9.2 7"/>
                                            <path d="M6.2 6.2C3.5 8 2 12 2 12s1 2 3.3 3.8"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <label class="check-control">
                                <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                                <span>Ingat perangkat ini</span>
                            </label>

                            <button class="auth-card__submit" type="submit">
                                Masuk <span aria-hidden="true">→</span>
                            </button>
                        </form>

                        <p class="auth-card__other">
                            Belum punya akun?
                            <a href="{{ route('register') }}" data-auth-view="signup">Buat akun</a>
                        </p>
                    </div>
                </section>
            </div>
        </div>

        @include('auth.partials.dataset-visual')
    </div>
</div>
@endsection