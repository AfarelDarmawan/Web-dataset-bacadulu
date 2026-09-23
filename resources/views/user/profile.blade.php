
@extends('layouts.public')

@section('title', 'Profil saya')

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
                $avatarUrl = route('media.avatar', ['filename' => basename($avatarPath)]);
            }
        }
    }

    $availableVariables = (int) ($stats['available_variables'] ?? 0);
    $approvedRequests = (int) ($stats['approved'] ?? 0);
@endphp

@section('content')
<section class="profile-esgi-page profile-page-v2 profile-inline-ui" data-profile-page>
    <div class="container-wide">
        <header class="profile-page-intro" data-profile-reveal>
            <div>
                <span class="profile-page-kicker">Akun peneliti</span>
                <h1 class="profile-page-heading profile-page-heading--v2">Profil saya</h1>
                <p class="profile-page-lead">
                    Kelola identitas akun dan pantau permintaan akses data penelitian dari satu tempat.
                </p>
            </div>

            <a class="profile-page-catalog-link" href="{{ route('datasets.index') }}">
                Jelajahi katalog <span aria-hidden="true">→</span>
            </a>
        </header>

        <section class="profile-esgi-card profile-esgi-card--identity profile-identity-card-v2" data-profile-reveal>
            <div class="profile-esgi-card__top profile-identity-top-v2">
                <div class="profile-esgi-user profile-user-v2">
                    <div
                        class="profile-esgi-avatar profile-avatar-v2"
                        data-gsap-avatar
                        role="img"
                        aria-label="Foto profil {{ $profileName }}"
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

                    <div class="profile-user-copy-v2">
                        <span class="profile-user-label">Peneliti terdaftar</span>
                        <h2>{{ $profileName }}</h2>
                        <p>{{ $profileUser->email }}</p>
                    </div>
                </div>

                <div class="profile-identity-actions-v2">
                    <a class="button button--profile-edit" href="{{ route('user.profile.edit') }}">
                        Edit profil
                    </a>
                    <a class="profile-secondary-link" href="{{ route('datasets.index') }}">
                        Cari data
                    </a>
                </div>
            </div>

            <div class="profile-esgi-meta profile-meta-v2">
                <div>
                    <span>Status akun</span>
                    <strong>{{ $profileUser->isActive() ? 'Aktif' : 'Tidak aktif' }}</strong>
                </div>

                <div>
                    <span>Jenis akun</span>
                    <strong>Peneliti</strong>
                </div>

                <div>
                    <span>Institusi</span>
                    <strong>{{ $profileUser->institution ?: 'Belum diisi' }}</strong>
                </div>

                <div>
                    <span>Variabel tersedia</span>
                    <strong>{{ number_format($availableVariables) }}</strong>
                </div>
            </div>
        </section>

        <section class="profile-esgi-section profile-section-v2" data-profile-reveal>
            <div class="profile-section-heading-v2">
                <div>
                    <span class="profile-page-kicker">Akses penelitian</span>
                    <h2>Akses data aktif</h2>
                </div>
                <a href="{{ route('user.requests.index') }}">
                    Lihat permintaan <span aria-hidden="true">→</span>
                </a>
            </div>

            <div class="profile-esgi-card profile-access-card-v2">
                <div class="profile-access-mark-v2" aria-hidden="true">
                    {{ $approvedRequests > 0 ? '✓' : '—' }}
                </div>

                <div class="profile-access-copy-v2">
                    @if($approvedRequests > 0)
                        <strong>{{ number_format($approvedRequests) }} permintaan disetujui</strong>
                        <p>Data yang disetujui dapat dilihat dari riwayat permintaan akses.</p>
                    @else
                        <strong>Belum ada akses data aktif</strong>
                        <p>Ajukan akses atau cari data terbuka dari katalog variabel BacaDulu.</p>
                    @endif
                </div>

                @if($approvedRequests > 0)
                    <a class="button button--profile-edit" href="{{ route('user.requests.index', ['status' => 'approved']) }}">
                        Lihat akses
                    </a>
                @else
                    <a class="button button--profile-edit" href="{{ route('datasets.index') }}">
                        Cari data
                    </a>
                @endif
            </div>
        </section>

        <section class="profile-esgi-section profile-section-v2" data-profile-reveal>
            <div class="profile-section-heading-v2">
                <div>
                    <span class="profile-page-kicker">Aktivitas akun</span>
                    <h2>Riwayat permintaan data</h2>
                </div>
                <a href="{{ route('user.requests.index') }}">
                    Lihat semua <span aria-hidden="true">→</span>
                </a>
            </div>

            <div class="profile-esgi-card profile-esgi-card--history profile-history-card-v2">
                <nav class="profile-history-tabs" aria-label="Filter permintaan data">
                    <a class="is-active" href="{{ route('user.profile') }}">Semua</a>
                    <a href="{{ route('user.requests.index', ['status' => 'pending']) }}">Menunggu</a>
                    <a href="{{ route('user.requests.index', ['status' => 'approved']) }}">Disetujui</a>
                    <a href="{{ route('user.requests.index', ['status' => 'rejected']) }}">Ditolak</a>
                </nav>

                <div class="profile-history-tools profile-history-tools-v2">
                    <span class="profile-history-search">
                        <span aria-hidden="true">⌕</span>
                        Permintaan terbaru
                    </span>

                    <a class="button button--profile-download" href="{{ route('datasets.index') }}">
                        Tambah permintaan
                    </a>
                </div>

                <div class="table-scroll">
                    <table class="profile-history-table">
                        <thead>
                            <tr>
                                <th>Permintaan</th>
                                <th>Sumber data</th>
                                <th>Diajukan</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($recentRequests as $item)
                                <tr>
                                    <td>
                                        <strong>{{ $item->request_number }}</strong>
                                        <small>
                                            {{ count($item->variable_ids) }} variabel ·
                                            {{ number_format($item->estimated_cells) }} sel
                                        </small>
                                    </td>

                                    <td>
                                        {{ $item->dataset->title }}
                                        <small>{{ $item->dataset->provider->name }}</small>
                                    </td>

                                    <td>{{ $item->created_at->format('d M Y') }}</td>

                                    <td>
                                        <span class="status status--{{ $item->status }}">
                                            {{ ucfirst($item->status) }}
                                        </span>
                                    </td>

                                    <td>
                                        <a
                                            class="profile-history-action"
                                            href="{{ route('user.requests.show', $item) }}"
                                        >
                                            Detail
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="profile-history-empty">
                                        Belum ada riwayat permintaan data.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</section>

@endsection