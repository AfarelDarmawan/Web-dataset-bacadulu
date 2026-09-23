@php
    $isMetadata = request()->routeIs('admin.datasets.edit');
    $isVariables = request()->routeIs('admin.datasets.variables.*');
    $isInput = request()->routeIs(
        'admin.datasets.import.*',
        'admin.automation.*',
        'admin.ai.*',
        'admin.source-documents.*',
        'admin.ai-extractions.*'
    );
    $isQuality = request()->routeIs('admin.datasets.observations.*');

    $inputUrl = request()->routeIs('admin.automation.*')
        ? route('admin.automation.index', ['dataset' => $currentDataset->id])
        : (request()->routeIs('admin.ai.*', 'admin.source-documents.*', 'admin.ai-extractions.*')
            ? route('admin.ai.index', ['dataset' => $currentDataset->id])
            : route('admin.datasets.import.create', $currentDataset));
@endphp

<section class="admin-collection-context" aria-label="Koleksi yang sedang dikerjakan">
    <div class="admin-collection-context__head">
        <div>
            <span>Koleksi yang sedang dikerjakan</span>
            <strong>{{ $currentDataset->title }}</strong>
            <small>{{ $currentDataset->code }} · {{ $currentDataset->scopeLabel() }}</small>
        </div>
        <a href="{{ route('admin.datasets.index') }}">Ganti koleksi</a>
    </div>

    <nav class="admin-collection-steps" aria-label="Tahapan koleksi">
        <a class="{{ $isMetadata ? 'is-current' : '' }}"
           href="{{ route('admin.datasets.edit', $currentDataset) }}"
           @if($isMetadata) aria-current="step" @endif>
            <span>01</span>Atur koleksi
        </a>

        <a class="{{ $isVariables ? 'is-current' : '' }}"
           href="{{ route('admin.datasets.variables.index', $currentDataset) }}"
           @if($isVariables) aria-current="step" @endif>
            <span>02</span>Variabel
        </a>

        <a class="{{ $isInput ? 'is-current' : '' }}"
           href="{{ $inputUrl }}"
           @if($isInput) aria-current="step" @endif>
            <span>03</span>Isi data
        </a>

        <a class="{{ $isQuality ? 'is-current' : '' }}"
           href="{{ route('admin.datasets.observations.index', $currentDataset) }}"
           @if($isQuality) aria-current="step" @endif>
            <span>04</span>Periksa angka
        </a>

        <a href="{{ route('admin.datasets.edit', $currentDataset) }}#publication">
            <span>05</span>Terbitkan
        </a>
    </nav>
</section>