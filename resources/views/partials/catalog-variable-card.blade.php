@php
    $dataset = $variable->dataset;

    $firstPeriod = $variable->first_observation_period;
    $lastPeriod = $variable->last_observation_period;

    $periodRange = (
        $firstPeriod
        && $lastPeriod
        && $firstPeriod !== $lastPeriod
    )
        ? $firstPeriod.'–'.$lastPeriod
        : ($firstPeriod ?: $lastPeriod ?: '—');

    $accessLabels = [
        'open' => 'Terbuka',
        'restricted' => 'Terbatas',
        'commercial' => 'Berlisensi',
    ];

    $detailUrl = route(
        'datasets.variables.show',
        [$dataset->slug, $variable]
    );
@endphp

<article
    class="variable-row catalog-variable-card"
    data-catalog-card
>
    <div class="catalog-variable-card__topline">
        <div class="catalog-variable-card__tags">
            <code>{{ $variable->code }}</code>

            <span>
                {{ $variable->category ?: $dataset->category }}
            </span>
        </div>

        <span class="scope-badge scope-badge--{{ $dataset->data_scope }}">
            @if($dataset->isCorporate())
                <svg
                    aria-hidden="true"
                    viewBox="0 0 20 20"
                    fill="none"
                >
                    <path d="M4 17V7l6-4 6 4v10M7 17v-5h6v5M2 17h16"></path>
                </svg>
            @else
                <svg
                    aria-hidden="true"
                    viewBox="0 0 20 20"
                    fill="none"
                >
                    <path d="M10 18s6-4.4 6-9.5a6 6 0 1 0-12 0C4 13.6 10 18 10 18Z"></path>

                    <circle
                        cx="10"
                        cy="8.5"
                        r="2"
                    ></circle>
                </svg>
            @endif

            {{ $dataset->scopeLabel() }}
        </span>
    </div>

    <div class="catalog-variable-card__body">
        <h2>
            <a href="{{ $detailUrl }}">
                {{ $variable->name }}
            </a>
        </h2>

        <p>
            {{
                \Illuminate\Support\Str::limit(
                    $variable->definition ?: $dataset->summary,
                    115
                )
            }}
        </p>
    </div>

    <div class="catalog-variable-card__source">
        <span class="catalog-variable-card__source-icon">
            <svg
                aria-hidden="true"
                viewBox="0 0 24 24"
                fill="none"
            >
                <path d="M5 7.5C5 5.6 8.1 4 12 4s7 1.6 7 3.5S15.9 11 12 11 5 9.4 5 7.5Z"></path>

                <path d="M5 7.5v4c0 1.9 3.1 3.5 7 3.5s7-1.6 7-3.5v-4M5 11.5v4C5 17.4 8.1 19 12 19s7-1.6 7-3.5v-4"></path>
            </svg>
        </span>

        <span>
            <small>Sumber data</small>

            <strong>
                {{ $dataset->provider->name }}
            </strong>
        </span>
    </div>

    <dl class="catalog-variable-card__facts">
        <div>
            <dt>
                {{
                    $dataset->isCorporate()
                        ? 'Perusahaan'
                        : 'Wilayah'
                }}
            </dt>

            <dd>
                {{ number_format($variable->entities_count) }}
            </dd>
        </div>

        <div>
            <dt>Periode</dt>

            <dd>
                {{ $periodRange }}
            </dd>
        </div>

        <div>
            <dt>Satuan</dt>

            <dd title="{{ $variable->unit ?: 'Belum dicantumkan' }}">
                {{ $variable->unit ?: '—' }}
            </dd>
        </div>
    </dl>

    <footer class="catalog-variable-card__footer">
        <span class="access-label access-label--{{ $dataset->access_type }}">
            <i aria-hidden="true"></i>

            {{
                $accessLabels[$dataset->access_type]
                ?? ucfirst($dataset->access_type)
            }}
        </span>

        <span class="catalog-variable-card__observation">
            {{ number_format($variable->observations_count) }}
            observasi
        </span>

        <a
            class="catalog-variable-card__action"
            href="{{ $detailUrl }}"
        >
            Lihat detail

            <svg
                aria-hidden="true"
                viewBox="0 0 20 20"
                fill="none"
            >
                <path d="M4 10h12M11 5l5 5-5 5"></path>
            </svg>
        </a>
    </footer>
</article>