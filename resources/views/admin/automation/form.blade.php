@php($config = is_array($connector->config) ? $connector->config : [])
<div class="form-section">
    <div class="form-section__head"><span>01</span><div><strong>Tujuan data</strong><small>Pilih satu variabel katalog untuk menerima data BPS.</small></div></div>
    <div class="form-grid form-grid--2">
        <div class="field field--span-2"><label for="name">Nama konektor</label><input id="name" name="name" value="{{ old('name', $connector->name) }}" required maxlength="160" placeholder="Contoh: Jumlah penduduk provinsi dari BPS"><small>Nama internal agar admin mudah mengenali sinkronisasi.</small></div>
        <div class="field field--span-2"><label for="dataset_variable_id">Koleksi dan variabel tujuan</label><select id="dataset_variable_id" name="dataset_variable_id" required><option value="">Pilih variabel tujuan</option>@foreach($datasets as $dataset)<optgroup label="{{ $dataset->title }} — {{ $dataset->provider->name }}">@foreach($dataset->variables as $variable)<option value="{{ $variable->id }}" @selected((string) old('dataset_variable_id', $connector->dataset_variable_id) === (string) $variable->id)>{{ $variable->name }} [{{ $variable->code }}]{{ $variable->unit ? ' · '.$variable->unit : '' }}</option>@endforeach</optgroup>@endforeach</select><small>Belum ada pilihan? Buat dataset Statistik wilayah dan variabelnya terlebih dahulu.</small></div>
    </div>
</div>

<div class="form-section">
    <div class="form-section__head"><span>02</span><div><strong>Pemetaan WebAPI BPS</strong><small>Gunakan ID dari dokumentasi atau portal WebAPI BPS.</small></div></div>
    <div class="form-grid form-grid--2">
        <div class="field"><label for="domain">Domain BPS</label><input id="domain" name="domain" value="{{ old('domain', data_get($config, 'domain', '0000')) }}" required inputmode="numeric" pattern="[0-9]{4}" maxlength="4" placeholder="0000"><small>0000 untuk pusat; kode lain untuk provinsi/kabupaten.</small></div>
        <div class="field"><label for="variable_id">ID variabel BPS</label><input id="variable_id" name="variable_id" type="number" min="1" value="{{ old('variable_id', data_get($config, 'variable_id')) }}" required placeholder="Contoh: 145"></div>
        <div class="field field--span-2"><label for="period_ids">ID periode BPS</label><input id="period_ids" name="period_ids" value="{{ old('period_ids', data_get($config, 'period_ids')) }}" required maxlength="120" placeholder="115;116 atau 115:120"><small>Ini ID periode dari API, bukan selalu angka tahun. Pisahkan beberapa ID dengan titik koma atau rentang dengan titik dua.</small></div>
        <div class="field"><label for="derived_variable_id">ID turunan variabel <span>(bila tersedia)</span></label><input id="derived_variable_id" name="derived_variable_id" type="number" min="0" value="{{ old('derived_variable_id', data_get($config, 'derived_variable_id')) }}" placeholder="Contoh: 289"><small>Wajib diisi jika variabel BPS mempunyai lebih dari satu kategori turunan.</small></div>
        <div class="field"><label for="vertical_variable_id">ID wilayah/vertical <span>(opsional)</span></label><input id="vertical_variable_id" name="vertical_variable_id" type="number" min="0" value="{{ old('vertical_variable_id', data_get($config, 'vertical_variable_id')) }}" placeholder="Kosongkan untuk semua"></div>
        <div class="field"><label for="derived_period_id">ID turunan periode <span>(opsional)</span></label><input id="derived_period_id" name="derived_period_id" type="number" min="0" value="{{ old('derived_period_id', data_get($config, 'derived_period_id')) }}" placeholder="Misalnya kuartal/bulan"></div>
        <div class="field"><label for="language">Bahasa metadata</label><select id="language" name="language"><option value="ind" @selected(old('language', data_get($config, 'language', 'ind')) === 'ind')>Indonesia</option><option value="eng" @selected(old('language', data_get($config, 'language')) === 'eng')>English</option></select></div>
    </div>
    <div class="form-help"><strong>Tidak menemukan ID?</strong><p>Buka dokumentasi resmi WebAPI BPS, cari Dynamic Data, lalu catat domain, variable ID, dan period ID. Token API tetap disimpan di <code>.env</code>, bukan pada form ini.</p><a href="https://webapi.bps.go.id/documentation/" target="_blank" rel="noopener noreferrer">Buka dokumentasi BPS ↗</a></div>
</div>

<div class="form-section">
    <div class="form-section__head"><span>03</span><div><strong>Jadwal</strong><small>Sinkronisasi terjadwal tetap masuk staging dan tidak langsung terbit.</small></div></div>
    <div class="form-grid form-grid--2">
        <div class="field"><label for="schedule">Frekuensi sinkronisasi</label><select id="schedule" name="schedule" required>@foreach(['manual'=>'Manual saja','daily'=>'Setiap hari','weekly'=>'Setiap minggu','monthly'=>'Setiap bulan'] as $value=>$label)<option value="{{ $value }}" @selected(old('schedule', $connector->schedule ?? 'manual') === $value)>{{ $label }}</option>@endforeach</select></div>
        <div class="connector-safety"><strong>Tidak auto-publish</strong><p>Angka hanya masuk ke observasi setelah admin menerima staging. Perubahan data akan kembali melewati quality control.</p></div>
    </div>
</div>
