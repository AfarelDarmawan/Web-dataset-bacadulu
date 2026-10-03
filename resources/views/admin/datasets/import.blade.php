@extends('layouts.admin')

@section('title', 'Impor Observasi')
@section('page_title', 'Impor observasi')

@section('content')
<nav class="backline">
    <a href="{{ route('admin.datasets.edit', $dataset) }}">
        ← {{ $dataset->title }}
    </a>
</nav>

<div class="page-head">
    <div>
        <span class="eyebrow">
            CSV ingestion / {{ $dataset->scopeLabel() }}
        </span>

        <h1>Masukkan data tanpa format yang ribet.</h1>

        <p>
            Pilih variabel tujuan, unggah CSV, lalu data
            yang berhasil masuk akan langsung diarahkan
            ke tahap pemeriksaan.
        </p>
    </div>

    <div class="page-head__actions">
        <a
            class="button button--line"
            href="{{ asset(
                'assets/dataset-import-template.csv'
            ) }}"
            download
        >
            Template sederhana
        </a>

        <a
            class="button button--line"
            href="{{ asset(
                $dataset->isCorporate()
                    ? 'assets/dataset-import-example-corporate-esg.csv'
                    : 'assets/dataset-import-example-bps.csv'
            ) }}"
            download
        >
            Contoh
            {{ $dataset->isCorporate()
                ? 'perusahaan & ESG'
                : 'wilayah'
            }}
        </a>
    </div>
</div>

@if(session('import_report'))
    @php($report = session('import_report'))

    <section
        class="admin-next-action"
        aria-labelledby="csv-report-title"
    >
        <div>
            <span class="admin-next-action__label">
                Laporan impor terakhir
            </span>

            <h2 id="csv-report-title">
                Hasil impor ditampilkan apa adanya.
            </h2>

            <p>
                Masuk:
                <strong>
                    {{ number_format(
                        $report['imported'] ?? 0
                    ) }}
                </strong>
                baris ·

                Dilewati:
                <strong>
                    {{ number_format(
                        $report['skipped'] ?? 0
                    ) }}
                </strong>
                baris ·

                Baris kosong:
                <strong>
                    {{ number_format(
                        $report['empty_rows'] ?? 0
                    ) }}
                </strong>.
            </p>

            @if(!empty($report['target_variable']))
                <p>
                    Variabel tujuan:
                    <strong>
                        {{ $report['target_variable'] }}
                    </strong>.
                </p>
            @endif

            @if(!empty($report['reasons']))
                <ul>
                    @foreach(
                        $report['reasons']
                        as $reason => $total
                    )
                        <li>
                            {{ number_format($total) }}
                            baris: {{ $reason }}.
                        </li>
                    @endforeach
                </ul>
            @endif

            @if(!empty($report['examples']))
                <p>
                    <strong>
                        Contoh yang perlu diperbaiki:
                    </strong>
                </p>

                <ul>
                    @foreach(
                        $report['examples']
                        as $example
                    )
                        <li>{{ $example }}</li>
                    @endforeach
                </ul>
            @endif
        </div>

        <a
            class="button button--line"
            href="{{ route(
                'admin.datasets.variables.index',
                $dataset
            ) }}"
        >
            Lihat variabel
        </a>
    </section>
@endif

<nav
    class="admin-input-options"
    aria-label="Cara mengisi data koleksi"
>
    <span>Pilih cara mengisi</span>

    <a
        class="is-current"
        href="{{ route(
            'admin.datasets.import.create',
            $dataset
        ) }}"
        aria-current="page"
    >
        Impor CSV
    </a>

    @if(!$dataset->isCorporate())
        <a
            href="{{ route(
                'admin.automation.index',
                ['dataset' => $dataset->id]
            ) }}"
        >
            Sinkronisasi BPS
        </a>
    @endif

    <a
        href="{{ route(
            'admin.ai.index',
            ['dataset' => $dataset->id]
        ) }}"
    >
        Unggah dokumen
    </a>
</nav>

@if($activeVariables->isEmpty())
    <section
        class="admin-next-action"
        aria-labelledby="csv-blocker-title"
    >
        <div>
            <span class="admin-next-action__label">
                Belum bisa mengimpor
            </span>

            <h2 id="csv-blocker-title">
                Tambahkan minimal satu variabel aktif.
            </h2>

            <p>
                Variabel menjelaskan angka apa yang sedang
                dimasukkan, misalnya Jumlah Penduduk,
                Inflasi, atau Pendapatan.
            </p>
        </div>

        <a
            class="button button--ink"
            href="{{ route(
                'admin.datasets.variables.index',
                $dataset
            ) }}"
        >
            Atur variabel →
        </a>
    </section>
@else
    <section
        class="admin-next-action"
        aria-labelledby="csv-flow-title"
    >
        <div>
            <span class="admin-next-action__label">
                Alur sederhana
            </span>

            <h2 id="csv-flow-title">
                Pilih variabel → unggah CSV → periksa angka.
            </h2>

            <p>
                Kolom <code>variable_code</code> tidak
                diperlukan jika semua baris CSV dimasukkan
                ke satu variabel yang dipilih di bawah.
            </p>
        </div>

        <a
            class="button button--line"
            href="{{ route(
                'admin.datasets.observations.index',
                $dataset
            ) }}"
        >
            Lihat
            {{ number_format(
                $dataset->observations_count
            ) }}
            data saat ini
        </a>
    </section>
@endif

<div class="import-grid">
    <form
        class="panel upload-panel"
        method="POST"
        action="{{ route(
            'admin.datasets.import.store',
            $dataset
        ) }}"
        enctype="multipart/form-data"
    >
        @csrf

        @if($activeVariables->count() === 1)
            @php(
                $onlyVariable = $activeVariables->first()
            )

            <input
                type="hidden"
                name="variable_source"
                value="{{ $onlyVariable->id }}"
            >

            <div class="field">
                <label>Variabel tujuan</label>

                <div class="form-hint">
                    Data otomatis dimasukkan ke

                    <strong>
                        {{ $onlyVariable->name }}
                    </strong>

                    (<code>{{ $onlyVariable->code }}</code>).
                </div>
            </div>
        @elseif($activeVariables->count() > 1)
            <div class="field">
                <label for="variable_source">
                    Data CSV ini untuk variabel apa?
                </label>

                <select
                    id="variable_source"
                    name="variable_source"
                    required
                >
                    <option value="">
                        Pilih variabel tujuan
                    </option>

                    @foreach(
                        $activeVariables
                        as $variable
                    )
                        <option
                            value="{{ $variable->id }}"
                            @selected(
                                old('variable_source')
                                === (string) $variable->id
                            )
                        >
                            {{ $variable->name }}
                            ·
                            {{ $variable->code }}
                        </option>
                    @endforeach

                    <option
                        value="csv"
                        @selected(
                            old('variable_source') === 'csv'
                        )
                    >
                        Mode lanjutan — baca variable_code
                        dari CSV
                    </option>
                </select>

                <small>
                    Pilih satu variabel untuk seluruh baris.
                    Mode lanjutan hanya dipakai jika satu
                    file berisi beberapa variabel.
                </small>
            </div>
        @endif

        <label class="drop-field" for="file">
            <span class="drop-field__icon">
                CSV
            </span>

            <strong>Pilih file data</strong>

            <small>
                Maksimal
                {{ number_format(
                    config(
                        'bacadulu.catalog.import_max_kilobytes'
                    ) / 1024
                ) }}
                MB. Bisa memakai pemisah koma atau
                titik koma.
            </small>

            <input
                id="file"
                name="file"
                type="file"
                accept=".csv,.txt,text/csv"
                required
                data-file-input
                @disabled(
                    $activeVariables->isEmpty()
                )
            >

            <b data-file-name>
                Belum ada file dipilih
            </b>
        </label>

        <button
            class="button button--ink button--block"
            type="submit"
            @disabled(
                $activeVariables->isEmpty()
            )
        >
            Validasi dan impor
        </button>
    </form>

    <section class="panel import-guide">
        <span class="panel-kicker">
            Format fleksibel
        </span>

        <h2>Minimal hanya dua kolom</h2>

        <div class="schema-list">
            <div>
                <code>tahun</code>
                atau
                <code>period</code>

                <span>
                    Wajib. Contoh: 2024 atau 2024-Q1.
                </span>
            </div>

            <div>
                <code>nilai</code>
                atau
                <code>value</code>

                <span>
                    Wajib. Bisa berisi angka maupun teks.
                </span>
            </div>

            <div>
                <code>wilayah</code>

                <span>
                    Opsional. Jika kosong, sistem memakai
                    Indonesia.
                </span>
            </div>

            <div>
                <code>kode_wilayah</code>

                <span>
                    Opsional. Jika kosong, sistem membuat
                    kode dari nama wilayah.
                </span>
            </div>

            <div>
                <code>sumber</code>

                <span>
                    Opsional. Bisa berupa URL, nomor
                    halaman, atau nama tabel.
                </span>
            </div>

            <div>
                <code>variable_code</code>

                <span>
                    Hanya diperlukan untuk mode lanjutan
                    dengan banyak variabel.
                </span>
            </div>
        </div>

        <p class="form-hint">
            Contoh paling sederhana: header
            <code>tahun,nilai</code>, kemudian isi data
            pada baris-baris berikutnya.
        </p>
    </section>
</div>

<section class="panel">
    <div class="panel__head">
        <div>
            <span class="panel-kicker">
                Setelah impor
            </span>

            <h2>
                Sistem langsung menunjukkan langkah berikutnya
            </h2>
        </div>
    </div>

    <div class="schema-list">
        <div>
            <code>01</code>

            <span>
                <strong>Sistem membaca nama kolom.</strong>
                Tahun/periode dan nilai/value otomatis
                dikenali.
            </span>
        </div>

        <div>
            <code>02</code>

            <span>
                <strong>Data dimasukkan.</strong>
                Baris bermasalah ditampilkan bersama nomor
                baris dan alasannya.
            </span>
        </div>

        <div>
            <code>03</code>

            <span>
                <strong>Admin memeriksa.</strong>
                Data yang masuk langsung muncul pada
                halaman Periksa angka.
            </span>
        </div>

        <div>
            <code>04</code>

            <span>
                <strong>Admin menerbitkan.</strong>
                Data baru tampil di katalog setelah
                pemeriksaan selesai.
            </span>
        </div>
    </div>
</section>
@endsection