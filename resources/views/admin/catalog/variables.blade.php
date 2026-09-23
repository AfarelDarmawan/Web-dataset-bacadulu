@extends('layouts.admin')

@section('title', 'Katalog Variabel')
@section('page_title', 'Katalog variabel')

@section('content')
<div class="page-head" data-motion-item>
    <div>
        <span class="eyebrow">Pusat katalog penelitian</span>
        <h1>Semua variabel dalam satu meja.</h1>
        <p>Cari indikator lintas koleksi, sumber, perusahaan, ESG, BPS, dan statistik pemerintah tanpa membuka dataset satu per satu.</p>
    </div>
    <a class="button button--ink" href="{{ route('admin.datasets.index') }}">Kelola koleksi</a>
</div>

<div class="metric-strip metric-strip--three">
    <div><span>Total variabel</span><strong data-count="{{ $totalVariables }}">{{ number_format($totalVariables) }}</strong></div>
    <div><span>Aktif di katalog</span><strong data-count="{{ $activeVariables }}">{{ number_format($activeVariables) }}</strong></div>
    <div><span>Memiliki harga per sel</span><strong data-count="{{ $pricedVariables }}">{{ number_format($pricedVariables) }}</strong></div>
</div>

<form class="toolbar toolbar--catalog-admin" method="GET">
    <div class="field field--search">
        <label for="q">Cari variabel</label>
        <input id="q" name="q" value="{{ request('q') }}" maxlength="100" placeholder="Nama, kode, definisi, atau dataset">
    </div>
    <div class="field">
        <label for="scope">Jenis data</label>
        <select id="scope" name="scope">
            <option value="">Semua jenis</option>
            <option value="corporate" @selected(request('scope') === 'corporate')>Perusahaan & ESG</option>
            <option value="regional" @selected(request('scope') === 'regional')>BPS & pemerintah</option>
        </select>
    </div>
    <div class="field">
        <label for="provider">Sumber</label>
        <select id="provider" name="provider">
            <option value="">Semua sumber</option>
            @foreach($providers as $provider)
                <option value="{{ $provider->id }}" @selected((string) request('provider') === (string) $provider->id)>{{ $provider->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="field">
        <label for="access">Tier data</label>
        <select id="access" name="access">
            <option value="">Semua tier</option>
            <option value="open" @selected(request('access') === 'open')>Terbuka</option>
            <option value="standard" @selected(request('access') === 'standard')>Standard</option>
            <option value="premium" @selected(request('access') === 'premium')>Premium</option>
        </select>
    </div>
    <div class="field">
        <label for="status">Status</label>
        <select id="status" name="status">
            <option value="">Semua status</option>
            <option value="active" @selected(request('status') === 'active')>Aktif</option>
            <option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option>
        </select>
    </div>
    <button class="button button--ink" type="submit">Terapkan</button>
    @if(request()->hasAny(['q', 'scope', 'provider', 'access', 'status']))
        <a class="toolbar-reset" href="{{ route('admin.catalog.variables.index') }}">Hapus filter</a>
    @endif
</form>

<section class="panel panel--flush">
    <div class="panel__head panel__head--padded">
        <div><span class="panel-kicker">Variable registry</span><h2>{{ number_format($variables->total()) }} hasil</h2></div>
        <small>Satu variabel dapat memiliki banyak perusahaan/wilayah dan periode.</small>
    </div>

    @if($variables->isEmpty())
        <div class="panel-empty panel-empty--borderless"><span>Variabel tidak ditemukan</span><p>Ubah filter atau tambahkan variabel melalui koleksi data.</p></div>
    @else
        <div class="table-scroll">
            <table class="data-table admin-table variable-catalog-table">
                <thead><tr><th>Variabel</th><th>Koleksi & sumber</th><th>Cakupan</th><th>Format</th><th>Akses</th><th>Isi</th><th></th></tr></thead>
                <tbody>
                    @foreach($variables as $variable)
                        <tr>
                            <td><strong>{{ $variable->name }}</strong><small><code>{{ $variable->code }}</code> · {{ $variable->category ?: 'Tanpa kategori' }}</small></td>
                            <td><strong>{{ $variable->dataset->title }}</strong><small>{{ $variable->dataset->provider->name }}</small></td>
                            <td><span class="scope-badge scope-badge--{{ $variable->dataset->data_scope }}">{{ $variable->dataset->scopeLabel() }}</span></td>
                            <td>{{ ucfirst($variable->data_type) }}<small>{{ $variable->unit ?: 'Tanpa satuan' }}</small></td>
                            <td><span class="access-label access-label--{{ $variable->access_tier }}">{{ ucfirst($variable->access_tier) }}</span><small>@if((float) $variable->price_per_cell > 0) Rp{{ number_format((float) $variable->price_per_cell, 0, ',', '.') }}/sel @else Tanpa biaya @endif</small></td>
                            <td>{{ number_format($variable->observations_count) }} observasi<small>{{ number_format($variable->data_connectors_count) }} konektor · {{ $variable->is_active ? 'aktif' : 'nonaktif' }}</small></td>
                            <td class="table-actions"><a href="{{ route('admin.datasets.variables.edit', [$variable->dataset, $variable]) }}">Atur</a><a href="{{ route('admin.datasets.observations.index', $variable->dataset) }}">QC</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap pagination-wrap--panel">{{ $variables->links() }}</div>
    @endif
</section>
@endsection
