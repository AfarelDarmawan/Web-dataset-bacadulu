@extends('layouts.public')

@section('title', $dataset->title.' — BacaDulu Dataset')
@section('meta_description', $dataset->summary)

@section('content')
<section class="dataset-detail-hero">
    <div class="container-wide">
        <nav class="breadcrumb" aria-label="Breadcrumb"><a href="{{ route('datasets.index') }}">Katalog variabel</a><span>/</span><span>{{ $dataset->code }}</span></nav>
        <div class="dataset-detail-hero__grid">
            <div>
                <div class="dataset-detail-hero__meta"><span>{{ $dataset->category }}</span><span class="scope-badge scope-badge--{{ $dataset->data_scope }}">{{ $dataset->scopeLabel() }}</span><span class="access-label access-label--{{ $dataset->access_type }}">{{ ['open' => 'Terbuka', 'restricted' => 'Terbatas', 'commercial' => 'Berlisensi'][$dataset->access_type] }}</span></div>
                <h1>{{ $dataset->title }}</h1>
                <p>{{ $dataset->summary }}</p>
            </div>
            <div class="dataset-identity">
                <span class="provider-monogram provider-monogram--large">{{ mb_strtoupper(mb_substr($dataset->provider->name, 0, 2)) }}</span>
                <div><small>Disediakan oleh</small><strong>{{ $dataset->provider->name }}</strong><span>{{ ucfirst($dataset->provider->type) }}</span></div>
            </div>
        </div>
        <dl class="dataset-detail-facts">
            <div><dt>Kode</dt><dd>{{ $dataset->code }}</dd></div>
            <div><dt>Periode</dt><dd>{{ $dataset->period_start && $dataset->period_end ? $dataset->period_start.'–'.$dataset->period_end : 'Belum ditentukan' }}</dd></div>
            <div><dt>Frekuensi</dt><dd>{{ $dataset->frequency ?: '—' }}</dd></div>
            <div><dt>Cakupan entitas</dt><dd>{{ $dataset->geographic_level ?: $dataset->entityLabel() }}</dd></div>
            <div><dt>Variabel</dt><dd>{{ number_format($dataset->variables_count) }}</dd></div>
            <div><dt>Observasi</dt><dd>{{ number_format($dataset->observations_count) }}</dd></div>
        </dl>
    </div>
</section>

<section class="section dataset-detail">
    <div class="container-wide dataset-detail__layout">
        <div class="dataset-detail__main">
            <section class="detail-section">
                <div class="detail-section__number">01</div>
                <div><h2>Tentang dataset</h2><div class="prose">{!! nl2br(e($dataset->description ?: $dataset->summary)) !!}</div></div>
            </section>

            <section class="detail-section" id="variables">
                <div class="detail-section__number">02</div>
                <div>
                    <div class="detail-heading"><div><h2>Kamus variabel</h2><p>Definisi, satuan, dan kategori akses untuk setiap kolom.</p></div><span>{{ $dataset->variables->count() }} variabel aktif</span></div>
                    @if ($dataset->variables->isEmpty())
                        <div class="inline-empty">Belum ada variabel yang dipublikasikan.</div>
                    @else
                        <div class="table-scroll"><table class="data-table"><thead><tr><th>Kode</th><th>Variabel</th><th>Definisi</th><th>Satuan</th><th>Tipe</th><th>Akses</th></tr></thead><tbody>@foreach ($dataset->variables as $variable)<tr><td><code>{{ $variable->code }}</code></td><td><strong><a href="{{ route('datasets.variables.show', [$dataset->slug, $variable]) }}">{{ $variable->name }}</a></strong></td><td>{{ $variable->definition ?: '—' }}</td><td>{{ $variable->unit ?: '—' }}</td><td>{{ ['numeric'=>'Numerik','text'=>'Teks','percentage'=>'Persentase','currency'=>'Mata uang','index'=>'Indeks'][$variable->data_type] ?? ucfirst($variable->data_type) }}</td><td><span class="mini-status mini-status--{{ $variable->access_tier }}">{{ ['open'=>'Terbuka','standard'=>'Standar','premium'=>'Premium'][$variable->access_tier] ?? ucfirst($variable->access_tier) }}</span></td></tr>@endforeach</tbody></table></div>
                    @endif
                </div>
            </section>

            <section class="detail-section" id="preview">
                <div class="detail-section__number">03</div>
                <div>
                    <div class="detail-heading"><div><h2>Pratinjau observasi</h2><p>{{ $dataset->isOpen() ? 'Contoh terbatas untuk memeriksa struktur data.' : 'Struktur dan cakupan ditampilkan tanpa membuka nilai data yang dilindungi.' }}</p></div></div>
                    <form class="preview-filter" method="GET" action="{{ route('datasets.show', $dataset->slug) }}#preview">
                        <select name="variable" aria-label="Filter variabel"><option value="">Semua variabel</option>@foreach($dataset->variables as $variable)<option value="{{ $variable->id }}" @selected((string)request('variable') === (string)$variable->id)>{{ $variable->name }}</option>@endforeach</select>
                        <select name="period" aria-label="Filter periode"><option value="">Semua periode</option>@foreach($periods as $period)<option value="{{ $period }}" @selected(request('period') === $period)>{{ $period }}</option>@endforeach</select>
                        <select name="geography" aria-label="Filter {{ $dataset->entityLabelLower() }}"><option value="">Semua {{ $dataset->entityLabelLower() }}</option>@foreach($geographies as $geography)<option value="{{ $geography->geography_code }}" @selected(request('geography') === $geography->geography_code)>{{ $geography->geography_name }}</option>@endforeach</select>
                        <button type="submit">Tampilkan</button>
                    </form>
                    @if ($observations->isEmpty())
                        <div class="inline-empty">Belum ada observasi untuk kombinasi filter ini.</div>
                    @else
                        <div class="table-scroll"><table class="data-table data-table--preview"><thead><tr><th>Variabel</th><th>{{ $dataset->entityLabel() }}</th><th>Periode</th><th>Nilai</th><th>Kualitas</th></tr></thead><tbody>@foreach($observations as $observation)<tr><td><code>{{ $observation->variable->code }}</code><small>{{ $observation->variable->name }}</small></td><td>{{ $observation->geography_name }}</td><td>{{ $observation->period }}</td><td>@if($dataset->isOpen())<strong>{{ $observation->displayValue() }}</strong> <small>{{ $observation->variable->unit }}</small>@else<span class="protected-value">Dilindungi</span>@endif</td><td><span class="mini-status mini-status--{{ $observation->quality_status }}">{{ ucfirst($observation->quality_status) }}</span></td></tr>@endforeach</tbody></table></div>
                    @endif
                </div>
            </section>

            <section class="detail-section">
                <div class="detail-section__number">04</div>
                <div><h2>Metodologi dan sumber</h2><div class="prose">{!! nl2br(e($dataset->methodology ?: 'Metodologi belum dilengkapi oleh pengelola dataset.')) !!}</div>@if($dataset->source_url)<a class="source-link" href="{{ $dataset->source_url }}" target="_blank" rel="noopener noreferrer">Buka sumber asli ↗</a>@endif</div>
            </section>
        </div>

        <aside class="access-panel" id="access">
            <div class="access-panel__sticky">
                <span class="eyebrow">Bangun keluaran data</span>
                <h2>{{ $dataset->isOpen() ? 'Ekspor data terbuka' : 'Ajukan akses data' }}</h2>
                <p>Pilih variabel serta cakupan yang benar-benar dibutuhkan. Pilihan kosong pada {{ $dataset->entityLabelLower() }} atau periode berarti seluruh cakupan.</p>

                @if(! auth('web')->check() || ! auth('web')->user()->isResearcher())
                    <div class="access-login"><strong>Masuk untuk melanjutkan</strong><p>Akun diperlukan agar riwayat akses dan unduhan dapat dicatat.</p><a class="button button--ink button--block" href="{{ route('login', ['redirect' => request()->getRequestUri()]) }}">Masuk sebagai peneliti</a><a class="text-link" href="{{ route('register') }}">Belum punya akun? Daftar</a></div>
                @else
                    @if($dataset->variables->isEmpty())
                        <div class="inline-empty">Belum ada variabel aktif yang dapat dipilih.</div>
                    @else
                        <form method="POST" action="{{ $dataset->isOpen() ? route('datasets.download.open', $dataset->slug) : route('datasets.request', $dataset->slug) }}" class="access-form">
                            @csrf
                            <fieldset><legend>Variabel <small>{{ $dataset->isOpen() ? 'opsional — kosong berarti semua' : 'wajib pilih' }}</small></legend><div class="check-list">@foreach($dataset->variables as $variable)<label><input type="checkbox" name="{{ $dataset->isOpen() ? 'variables[]' : 'variable_ids[]' }}" value="{{ $variable->id }}" @checked((string) request('variable') === (string) $variable->id)><span><strong>{{ $variable->name }}</strong><small>{{ $variable->code }} · {{ $variable->unit ?: 'tanpa satuan' }}</small></span></label>@endforeach</div></fieldset>
                            @if($geographies->isNotEmpty())<fieldset><legend>{{ $dataset->entityLabel() }} <small>opsional</small></legend><div class="check-list check-list--compact">@foreach($geographies as $geography)<label><input type="checkbox" name="geographies[]" value="{{ $geography->geography_code }}"><span>{{ $geography->geography_name }}</span></label>@endforeach</div></fieldset>@endif
                            @if($periods->isNotEmpty())<fieldset><legend>Periode <small>opsional</small></legend><div class="check-list check-list--chips">@foreach($periods as $period)<label><input type="checkbox" name="periods[]" value="{{ $period }}"><span>{{ $period }}</span></label>@endforeach</div></fieldset>@endif
                            @unless($dataset->isOpen())<div class="field"><label for="research_purpose">Tujuan penggunaan</label><textarea id="research_purpose" name="research_purpose" rows="5" minlength="20" required placeholder="Jelaskan topik, tujuan, dan bentuk penelitian yang akan menggunakan data ini.">{{ old('research_purpose') }}</textarea></div>@endunless
                            <button class="button button--ink button--block" type="submit">{{ $dataset->isOpen() ? 'Unduh CSV' : 'Kirim permintaan akses' }}</button>
                        </form>
                    @endif
                @endif

                <dl class="access-panel__notes"><div><dt>Lisensi</dt><dd>{{ $dataset->license ?: 'Mengikuti kebijakan provider' }}</dd></div><div><dt>Diperbarui</dt><dd>{{ optional($dataset->last_updated_at)->format('d M Y') ?: '—' }}</dd></div></dl>
            </div>
        </aside>
    </div>
</section>
@endsection
