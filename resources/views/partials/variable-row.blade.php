@php
    $rowDataset = $variable->dataset;

    $periodRange = $variable->first_observation_period === $variable->last_observation_period
        ? $variable->first_observation_period
        : $variable->first_observation_period.'–'.$variable->last_observation_period;

    $accessLabels = [
        'open' => 'Terbuka',
        'restricted' => 'Terbatas',
        'commercial' => 'Berlisensi',
    ];
@endphp

<article class="variable-row">
    <div class="variable-row__identity">
        <div class="variable-row__eyeline">
            <code>{{ $variable->code }}</code>

            <span>
                {{ $variable->category ?: $rowDataset->category }}
            </span>
        </div>

        <h2>
            <a href="{{ route('datasets.variables.show', [$rowDataset->slug, $variable]) }}">
                {{ $variable->name }}
            </a>
        </h2>

        <p>
            {{
                \Illuminate\Support\Str::limit(
                    $variable->definition ?: $rowDataset->summary,
                    155
                )
            }}
        </p>

        <small>
            {{ $rowDataset->provider->name }}

            <i>·</i>

            {{ $rowDataset->title }}
        </small>
    </div>

    <div class="variable-row__scope">
        <span class="scope-badge scope-badge--{{ $rowDataset->data_scope }}">
            {{ $rowDataset->scopeLabel() }}
        </span>

        <small>
            {{
                $rowDataset->geographic_level
                ?: (
                    $rowDataset->isCorporate()
                        ? 'Entitas perusahaan'
                        : 'Cakupan wilayah'
                )
            }}
        </small>
    </div>

    <dl class="variable-row__availability">
        <div>
            <dt>
                {{
                    $rowDataset->isCorporate()
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
                {{ $periodRange ?: '—' }}
            </dd>
        </div>

        <div>
            <dt>Satuan</dt>

            <dd>
                {{ $variable->unit ?: '—' }}
            </dd>
        </div>
    </dl>

    <div class="variable-row__access">
        <span class="access-label access-label--{{ $rowDataset->access_type }}">
            {{
                $accessLabels[$rowDataset->access_type]
                ?? ucfirst($rowDataset->access_type)
            }}
        </span>

        <small>
            {{ number_format($variable->observations_count) }}
            observasi
        </small>
    </div>

    <a
        class="variable-row__arrow"
        href="{{ route('datasets.variables.show', [$rowDataset->slug, $variable]) }}"
        aria-label="Lihat {{ $variable->name }}"
    >
        →
    </a>
</article>