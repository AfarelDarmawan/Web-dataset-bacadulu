@extends('layouts.public')

@section(
    'title',
    $accessRequest->request_number
)

@php
    $whatsappNumber = (string) config(
        'bacadulu.contact.whatsapp_number'
    );

    $serviceHours = (string) config(
        'bacadulu.contact.service_hours'
    );

    $urgentResponse = (string) config(
        'bacadulu.contact.urgent_response'
    );

    $whatsappMessage = implode("\n", [
        'Halo Admin BacaDulu Dataset,',
        '',
        'Saya ingin meminta bantuan untuk permintaan data berikut:',
        'Nomor: '.$accessRequest->request_number,
        'Dataset: '.$accessRequest->dataset->title,
        'Nama: '.$accessRequest->user->name,
        'Email: '.$accessRequest->user->email,
        'Estimasi data: '.number_format(
            $accessRequest->estimated_cells,
            0,
            ',',
            '.'
        ).' sel',
        (float) $accessRequest->estimated_price > 0
            ? 'Estimasi biaya: Rp'.number_format(
                (float) $accessRequest->estimated_price,
                0,
                ',',
                '.'
            )
            : 'Biaya: tidak ada estimasi biaya',
        '',
        'Data ini saya butuhkan segera untuk penelitian. Mohon dibantu pengecekan prioritas dan langkah selanjutnya.',
        '',
        'Terima kasih.',
    ]);

    $whatsappUrl = $whatsappNumber !== ''
        ? 'https://wa.me/'
            .$whatsappNumber
            .'?text='
            .rawurlencode($whatsappMessage)
        : null;
@endphp

@section('content')
<section class="access-page access-page--detail">
    <div class="container-wide">
        <a
            class="access-page__back"
            href="{{ route('user.requests.index') }}"
        >
            ← Semua permintaan
        </a>

        <div
            class="request-detail-heading"
            data-motion-item
        >
            <div>
                <span class="eyebrow">
                    {{ $accessRequest->request_number }}
                </span>

                <h1>
                    {{ $accessRequest->dataset->title }}
                </h1>

                <p>
                    {{ $accessRequest->dataset->provider->name }}
                </p>
            </div>

            <span
                class="status status--large status--{{ $accessRequest->status }}"
            >
                {{ ucfirst($accessRequest->status) }}
            </span>
        </div>

        <div class="request-detail-grid">
            <section
                class="access-detail-card"
                data-motion-item
            >
                <div class="access-detail-card__head">
                    <div>
                        <span class="eyebrow">
                            Ruang lingkup
                        </span>

                        <h2>Data yang diajukan</h2>
                    </div>

                    <span class="access-detail-card__index">
                        01
                    </span>
                </div>

                <dl class="access-definition-list">
                    <div>
                        <dt>Variabel</dt>

                        <dd>
                            @foreach(
                                $accessRequest
                                    ->dataset
                                    ->variables
                                    ->whereIn(
                                        'id',
                                        $accessRequest->variable_ids
                                    )
                                as $variable
                            )
                                <span class="access-tag">
                                    {{ $variable->code }}
                                    ·
                                    {{ $variable->name }}
                                </span>
                            @endforeach
                        </dd>
                    </div>

                    <div>
                        <dt>
                            {{ $accessRequest->dataset->entityLabel() }}
                        </dt>

                        <dd>
                            {{
                                $accessRequest->geographies
                                    ? implode(
                                        ', ',
                                        $accessRequest->geographies
                                    )
                                    : 'Seluruh '
                                        .$accessRequest
                                            ->dataset
                                            ->entityLabelLower()
                                        .' tersedia'
                            }}
                        </dd>
                    </div>

                    <div>
                        <dt>Periode</dt>

                        <dd>
                            {{
                                $accessRequest->periods
                                    ? implode(
                                        ', ',
                                        $accessRequest->periods
                                    )
                                    : 'Seluruh periode tersedia'
                            }}
                        </dd>
                    </div>

                    <div>
                        <dt>Estimasi keluaran</dt>

                        <dd>
                            {{ number_format(
                                $accessRequest->estimated_cells
                            ) }}
                            sel data
                        </dd>
                    </div>

                    @if($accessRequest->requiresPayment())
                        <div>
                            <dt>Estimasi biaya</dt>

                            <dd>
                                Rp{{ number_format(
                                    (float) $accessRequest->estimated_price,
                                    0,
                                    ',',
                                    '.'
                                ) }}
                            </dd>
                        </div>
                    @endif
                </dl>
            </section>

            <aside
                class="access-detail-card access-detail-card--decision"
                data-motion-item
            >
                <div class="access-detail-card__head">
                    <div>
                        <span class="eyebrow">
                            Keputusan akses
                        </span>

                        <h2>Status permintaan</h2>
                    </div>

                    <span class="access-detail-card__index">
                        02
                    </span>
                </div>

                @if($accessRequest->status === 'pending')
                    <div
                        class="decision-mark decision-mark--pending"
                        aria-hidden="true"
                    >
                        …
                    </div>

                    @if($accessRequest->requiresPayment())
                        <h3>
                            {{ $accessRequest->paymentStatusLabel() }}
                        </h3>

                        <p>
                            Admin sedang memeriksa cakupan,
                            lisensi, dan harga final. Akses unduh
                            baru dibuka setelah pembayaran
                            terverifikasi.
                        </p>
                    @else
                        <h3>Sedang ditinjau</h3>

                        <p>
                            Admin sedang memeriksa tujuan
                            penggunaan dan cakupan data.
                            Keputusan akan muncul di halaman ini.
                        </p>
                    @endif

                @elseif($accessRequest->status === 'approved')
                    <div
                        class="decision-mark decision-mark--approved"
                        aria-hidden="true"
                    >
                        ✓
                    </div>

                    <h3>Akses disetujui</h3>

                    <p>
                        Data dapat diunduh sampai
                        {{
                            optional(
                                $accessRequest->expires_at
                            )->format('d M Y H:i')
                            ?: 'batas yang ditentukan pengelola'
                        }}.
                    </p>

                    @if(
                        $accessRequest->canDownload()
                        && $accessRequest
                            ->dataset
                            ->isPubliclyAvailable()
                    )
                        <form
                            method="POST"
                            action="{{ route(
                                'user.requests.download',
                                $accessRequest
                            ) }}"
                        >
                            @csrf

                            <button
                                class="button button--ink button--block"
                                type="submit"
                            >
                                Unduh CSV
                            </button>
                        </form>
                    @else
                        <div class="access-alert">
                            Akses berakhir atau dataset
                            sedang tidak tersedia.
                        </div>
                    @endif
                @else
                    <div
                        class="decision-mark decision-mark--rejected"
                        aria-hidden="true"
                    >
                        ×
                    </div>

                    <h3>Permintaan ditolak</h3>

                    <p>
                        Periksa catatan pengelola. Kamu dapat
                        mengajukan permintaan baru dengan
                        cakupan atau tujuan yang lebih jelas.
                    </p>
                @endif

                @if($accessRequest->admin_note)
                    <div class="admin-note">
                        <strong>Catatan admin</strong>

                        <p>
                            {{ $accessRequest->admin_note }}
                        </p>
                    </div>
                @endif
            </aside>
        </div>

        @if($accessRequest->requiresPayment())
            <section
                class="access-detail-card access-payment-card"
                data-motion-item
            >
                <div class="access-detail-card__head">
                    <div>
                        <span class="eyebrow">
                            Pembayaran dataset
                        </span>

                        <h2>
                            Bayar setelah rincian dikonfirmasi
                        </h2>
                    </div>

                    <span
                        class="payment-state payment-state--{{ $accessRequest->payment_status }}"
                    >
                        {{ $accessRequest->paymentStatusLabel() }}
                    </span>
                </div>

                <div class="payment-summary">
                    <div>
                        <span>Estimasi awal</span>

                        <strong>
                            Rp{{ number_format(
                                (float) $accessRequest->estimated_price,
                                0,
                                ',',
                                '.'
                            ) }}
                        </strong>

                        <small>
                            Harga final mengikuti cakupan dan
                            lisensi yang disetujui.
                        </small>
                    </div>

                    @if($accessRequest->payment_reference)
                        <div>
                            <span>Referensi pembayaran</span>

                            <strong>
                                {{ $accessRequest->payment_reference }}
                            </strong>

                            <small>
                                Sertakan kode ini ketika
                                mengirim bukti pembayaran.
                            </small>
                        </div>
                    @endif
                </div>

                <ol class="payment-steps">
                    <li class="{{
                        in_array(
                            $accessRequest->payment_status,
                            [
                                'awaiting_payment',
                                'payment_review',
                                'paid',
                            ],
                            true
                        )
                            ? 'is-complete'
                            : 'is-current'
                    }}">
                        <span>1</span>

                        <div>
                            <strong>
                                Konfirmasi harga final
                            </strong>

                            <p>
                                Admin memeriksa variabel,
                                periode, cakupan, dan aturan
                                lisensinya.
                            </p>
                        </div>
                    </li>

                    <li class="{{
                        $accessRequest->payment_status
                            === 'awaiting_payment'
                                ? 'is-current'
                                : (
                                    in_array(
                                        $accessRequest->payment_status,
                                        [
                                            'payment_review',
                                            'paid',
                                        ],
                                        true
                                    )
                                        ? 'is-complete'
                                        : ''
                                )
                    }}">
                        <span>2</span>

                        <div>
                            <strong>
                                Bayar melalui instruksi resmi
                            </strong>

                            <p>
                                Invoice dan kanal pembayaran
                                dikirim oleh admin lewat WhatsApp.
                            </p>
                        </div>
                    </li>

                    <li class="{{
                        $accessRequest->payment_status
                            === 'payment_review'
                                ? 'is-current'
                                : (
                                    $accessRequest->payment_status
                                    === 'paid'
                                        ? 'is-complete'
                                        : ''
                                )
                    }}">
                        <span>3</span>

                        <div>
                            <strong>
                                Verifikasi dan buka akses
                            </strong>

                            <p>
                                Kirim bukti bayar di percakapan
                                yang sama. Setelah diverifikasi,
                                admin membuka unduhan.
                            </p>
                        </div>
                    </li>
                </ol>
            </section>
        @endif

        <section
            class="access-priority-card"
            data-motion-item
        >
            <div class="access-priority-card__copy">
                <span class="eyebrow">
                    Bantuan prioritas
                </span>

                <h2>
                    Perlu data untuk penelitian hari ini?
                </h2>

                <p>
                    Nomor permintaan dan rincian dataset sudah
                    otomatis dimasukkan ke pesan. Admin tetap
                    perlu memeriksa ketersediaan serta lisensi
                    sebelum menjanjikan waktu selesai.
                </p>

                <small>
                    {{ $serviceHours }}
                    ·
                    {{ $urgentResponse }}
                </small>
            </div>

            @if($whatsappUrl)
                <a
                    class="access-whatsapp-button access-whatsapp-button--large"
                    href="{{ $whatsappUrl }}"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    <svg
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <path d="M12.04 2a9.84 9.84 0 00-8.42 14.93L2 22l5.2-1.58A9.9 9.9 0 1012.04 2zm0 17.82a7.9 7.9 0 01-4.03-1.1l-.29-.17-3.09.94.97-3-.19-.31a7.81 7.81 0 116.63 3.64zm4.31-5.86c-.24-.12-1.4-.69-1.62-.77-.22-.08-.37-.12-.53.12-.16.24-.61.77-.75.93-.14.16-.28.18-.52.06-.24-.12-1-.37-1.9-1.18-.7-.63-1.18-1.4-1.32-1.64-.14-.24-.01-.37.1-.49.11-.11.24-.28.35-.41.12-.14.16-.24.24-.4.08-.15.04-.29-.02-.41-.06-.12-.53-1.28-.73-1.75-.19-.46-.39-.4-.53-.4h-.45c-.16 0-.41.06-.63.29-.22.24-.83.81-.83 1.97s.85 2.29.96 2.45c.12.16 1.66 2.54 4.03 3.56.56.24 1 .39 1.35.49.57.18 1.08.15 1.49.09.45-.07 1.4-.57 1.6-1.12.2-.55.2-1.02.14-1.12-.06-.1-.22-.16-.46-.28z"/>
                    </svg>

                    Percepat via WhatsApp

                    <span aria-hidden="true">↗</span>
                </a>
            @else
                <div class="access-priority-card__offline">
                    Nomor WhatsApp call center belum
                    dikonfigurasi oleh admin.
                </div>
            @endif
        </section>

        <section
            class="access-detail-card access-detail-card--purpose"
            data-motion-item
        >
            <div class="access-detail-card__head">
                <div>
                    <span class="eyebrow">
                        Konteks penelitian
                    </span>

                    <h2>Tujuan penggunaan</h2>
                </div>

                <span class="access-detail-card__index">
                    03
                </span>
            </div>

            <div class="access-purpose">
                {!! nl2br(
                    e($accessRequest->research_purpose)
                ) !!}
            </div>
        </section>
    </div>
</section>
@endsection