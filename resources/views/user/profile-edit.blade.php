@extends('layouts.public')

@section('title', 'Edit profil')

@push('page_styles')
    <link rel="stylesheet" href="{{ asset('assets/researcher-profile.css') }}?v=8.1.0">
@endpush

@push('page_scripts')
    <script src="{{ asset('assets/researcher-profile.js') }}?v=8.0.0" defer></script>
@endpush

@php
    $profileName = trim((string) $profileUser->name);
    $nameParts = preg_split('/\s+/', $profileName, -1, PREG_SPLIT_NO_EMPTY);

    $initials = collect(array_slice($nameParts, 0, 2))
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');

    $storedAvatar = trim((string) $profileUser->avatar);
    $avatarUrl = null;

    if ($storedAvatar !== '') {
        if (str_starts_with($storedAvatar, 'http://') || str_starts_with($storedAvatar, 'https://')) {
            $avatarUrl = $storedAvatar;
        } else {
            $avatarPath = preg_replace('#^(storage/|public/)#i', '', ltrim($storedAvatar, '/'));
            $avatarDisk = \Illuminate\Support\Facades\Storage::disk('public');

            if (preg_match('#\Aavatars/[A-Za-z0-9._-]+\z#', $avatarPath) && $avatarDisk->exists($avatarPath)) {
                $avatarUrl = route('media.avatar', [
                    'filename' => basename($avatarPath),
                ]);
            }
        }
    }
@endphp

@section('content')
<section class="profile-esgi-page profile-edit-page-v2" data-edit-profile-page>
    <div class="container-wide">
        <a class="profile-edit-back profile-edit-back-v2" href="{{ route('user.profile') }}">
            <span aria-hidden="true">←</span>
            Kembali ke profil
        </a>

        <header class="profile-edit-hero-v2" data-profile-reveal>
            <div>
                <span class="profile-page-kicker">Pengaturan akun</span>
                <h1>Edit profil</h1>
                <p>
                    Pastikan identitasmu tetap akurat ketika mengajukan akses data penelitian.
                </p>
            </div>

            <div class="profile-edit-context">
                <span>01 / 01</span>
                <small>Pengaturan profil peneliti</small>
            </div>
        </header>

        <div class="profile-edit-layout profile-edit-layout-v2">
            <aside class="profile-edit-side-v2" data-profile-reveal>
                <div class="profile-edit-preview-card-v2">
                    <span class="profile-page-kicker">Foto profil</span>

                    <div
                        class="profile-photo-preview profile-photo-preview-v2"
                        data-avatar-preview
                        data-gsap-avatar
                        role="img"
                        aria-label="Pratinjau foto profil {{ $profileName }}"
                    >
                        @if($avatarUrl)
                            <img
                                src="{{ $avatarUrl }}"
                                alt="Foto profil {{ $profileName }}"
                                data-profile-avatar-image
                            >
                            <span data-profile-avatar-fallback hidden>{{ $initials ?: 'P' }}</span>
                        @else
                            <span>{{ $initials ?: 'P' }}</span>
                        @endif
                    </div>

                    <h2>{{ $profileName }}</h2>
                    <p>{{ $profileUser->email }}</p>

                    <div class="profile-edit-preview-rule"></div>

                    <small>
                        Foto ini akan tampil pada halaman profil dan menu akun di navbar.
                    </small>
                </div>

                <div class="profile-edit-side-note-v2">
                    <span class="profile-edit-side-note-v2__mark">i</span>
                    <div>
                        <strong>Gunakan foto yang mudah dikenali</strong>
                        <p>Format JPG, PNG, atau WebP dengan ukuran maksimal 5 MB.</p>
                    </div>
                </div>
            </aside>

            <section class="profile-esgi-card profile-edit-card profile-edit-card-v2" data-profile-reveal>
                <div class="profile-edit-card-heading-v2">
                    <div>
                        <span class="profile-page-kicker">Informasi akun</span>
                        <h2>Perbarui identitas</h2>
                    </div>

                    <span class="profile-edit-required-note">
                        Data bertanda * wajib diisi
                    </span>
                </div>

                <form
                    method="POST"
                    action="{{ route('user.profile.update') }}"
                    class="profile-edit-form profile-edit-form-v2"
                    enctype="multipart/form-data"
                    data-busy-form
                >
                    @csrf
                    @method('PATCH')

                    <div class="profile-edit-upload-v2">
                        <div>
                            <label for="avatar">Ganti foto profil</label>
                            <p class="field-help">
                                Foto akan dipotong otomatis menjadi bentuk lingkaran.
                            </p>
                        </div>

                        <label class="profile-file-picker-v2" for="avatar">
                            <span>Pilih foto</span>
                            <input
                                id="avatar"
                                name="avatar"
                                type="file"
                                accept="image/jpeg,image/png,image/webp"
                                data-profile-avatar-input
                            >
                        </label>

                        @error('avatar')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="profile-edit-form-divider-v2">
                        <span>Identitas dasar</span>
                    </div>

                    <div class="profile-edit-field-grid-v2">
                        <div class="field profile-field-v2 profile-field-v2--wide">
                            <label for="name">
                                Nama lengkap <span>*</span>
                            </label>

                            <input
                                id="name"
                                name="name"
                                type="text"
                                value="{{ old('name', $profileUser->name) }}"
                                maxlength="120"
                                autocomplete="name"
                                required
                            >

                            @error('name')
                                <small class="field-error">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="field profile-field-v2 profile-field-v2--wide">
                            <label for="email">Alamat email</label>

                            <input
                                id="email"
                                type="email"
                                value="{{ $profileUser->email }}"
                                readonly
                            >

                            <small class="field-help">
                                Email digunakan sebagai identitas login dan tidak diubah dari halaman ini.
                            </small>
                        </div>

                        <div class="field profile-field-v2 profile-field-v2--wide">
                            <label for="institution">Institusi</label>

                            <input
                                id="institution"
                                name="institution"
                                type="text"
                                value="{{ old('institution', $profileUser->institution) }}"
                                maxlength="190"
                                autocomplete="organization"
                                placeholder="Nama universitas, lembaga, atau perusahaan"
                            >

                            <small class="field-help">
                                Institusi membantu pengelola memahami konteks permintaan data.
                            </small>

                            @error('institution')
                                <small class="field-error">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    <div class="profile-edit-actions profile-edit-actions-v2">
                        <a
                            class="button button--line"
                            href="{{ route('user.profile') }}"
                        >
                            Batal
                        </a>

                        <button
                            class="button button--ink profile-save-button-v2"
                            type="submit"
                        >
                            Simpan perubahan
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </div>
</section>

@endsection
