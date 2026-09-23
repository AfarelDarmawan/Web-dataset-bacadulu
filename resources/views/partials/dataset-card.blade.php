<article class="dataset-card" data-reveal>
    <div class="dataset-card__meta">
        <span>{{ $dataset->category }}</span>
        <span class="access-label access-label--{{ $dataset->access_type }}">
            {{ ['open' => 'Terbuka', 'restricted' => 'Terbatas', 'commercial' => 'Berlisensi'][$dataset->access_type] ?? ucfirst($dataset->access_type) }}
        </span>
    </div>
    <h3><a href="{{ route('datasets.show', $dataset->slug) }}">{{ $dataset->title }}</a></h3>
    <p>{{ $dataset->summary }}</p>
    <div class="dataset-card__provider">
        <span class="provider-monogram">{{ mb_strtoupper(mb_substr($dataset->provider->name, 0, 2)) }}</span>
        <div><strong>{{ $dataset->provider->name }}</strong><small>{{ $dataset->code }}</small></div>
    </div>
    <dl class="dataset-card__facts">
        <div><dt>Variabel</dt><dd>{{ number_format($dataset->variables_count ?? 0) }}</dd></div>
        <div><dt>Observasi</dt><dd>{{ number_format($dataset->observations_count ?? 0) }}</dd></div>
        <div><dt>Periode</dt><dd>{{ $dataset->period_start && $dataset->period_end ? $dataset->period_start.'–'.$dataset->period_end : '—' }}</dd></div>
    </dl>
    <a class="dataset-card__link" href="{{ route('datasets.show', $dataset->slug) }}">Lihat struktur data <span aria-hidden="true">→</span></a>
</article>
