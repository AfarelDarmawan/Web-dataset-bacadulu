@extends('layouts.admin-auth')

@section('title', 'Masuk Admin')

@section('content')
<div class="admin-login-heading">
    <span class="admin-login-heading__eyebrow">Akses khusus pengelola</span>
    <h2>Selamat datang kembali.</h2>
    <p>Masuk untuk melanjutkan pengelolaan dan pemeriksaan data.</p>
</div>

<form class="admin-login-form" method="POST" action="{{ route('admin.login.store') }}">
    @csrf

    <div class="admin-login-field">
        <label for="admin-email">Email administrator</label>
        <input
            id="admin-email"
            name="email"
            type="email"
            value="{{ old('email') }}"
            autocomplete="username"
            inputmode="email"
            maxlength="190"
            required
            autofocus
            @if($errors->has('email')) aria-invalid="true" @endif
        >
    </div>

    <div class="admin-login-field">
        <label for="admin-password">Kata sandi</label>
        <div class="admin-login-password">
            <input
                id="admin-password"
                name="password"
                type="password"
                autocomplete="current-password"
                maxlength="128"
                required
                @if($errors->has('password')) aria-invalid="true" @endif
            >
            <button
                type="button"
                data-admin-password-toggle
                aria-controls="admin-password"
                aria-pressed="false"
            >Lihat</button>
        </div>
    </div>

    <label class="admin-login-remember" for="admin-remember">
        <input
            id="admin-remember"
            name="remember"
            type="checkbox"
            value="1"
            @checked(old('remember'))
        >
        <span>Ingat saya di perangkat ini</span>
    </label>

    <button class="admin-login-submit" type="submit">
        <span>Masuk ke panel</span>
        <span aria-hidden="true">→</span>
    </button>
</form>

<p class="admin-login-help">
    Gunakan email dan kata sandi admin yang telah dikonfigurasi untuk situs ini.
</p>
@endsection