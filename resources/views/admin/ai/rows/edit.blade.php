@extends('layouts.admin')

@section('title', 'Edit Kandidat #'.$row->row_index)
@section('page_title', 'Edit kandidat ekstraksi')

@section('content')
<nav class="backline"><a href="{{ route('admin.ai-extractions.show', $job) }}">← Review job #{{ $job->id }}</a></nav>
<div class="page-head"><div><span class="eyebrow">Baris staging {{ $row->row_index }}</span><h1>Periksa terhadap sumber.</h1><p>Edit hanya berdasarkan dokumen <strong>{{ $job->sourceDocument->original_name }}</strong>. Jangan mengisi angka dari asumsi.</p></div><span class="mini-status mini-status--{{ $row->validation_status }}">{{ $row->validation_status === 'warning' ? 'Peringatan' : ($row->validation_status === 'invalid' ? 'Tidak valid' : 'Valid') }}</span></div>

<div class="edit-evidence-grid">
    <form class="panel form-panel" method="POST" action="{{ route('admin.ai-extractions.rows.update', [$job, $row]) }}">
        @csrf @method('PATCH')
        <div class="form-grid form-grid--2">
            <div class="field"><label for="variable_code">Kode variabel</label><input id="variable_code" name="variable_code" value="{{ old('variable_code', $row->variable_code) }}" required maxlength="100"></div>
            <div class="field"><label for="variable_name">Nama variabel</label><input id="variable_name" name="variable_name" value="{{ old('variable_name', $row->variable_name) }}" required maxlength="255"></div>
            <div class="field field--span-2"><label for="variable_definition">Definisi</label><textarea id="variable_definition" name="variable_definition" rows="3" maxlength="5000">{{ old('variable_definition', $row->variable_definition) }}</textarea></div>
            <div class="field"><label for="unit">Satuan</label><input id="unit" name="unit" value="{{ old('unit', $row->unit) }}" maxlength="80"></div>
            <div class="field"><label for="data_type">Tipe data</label><select id="data_type" name="data_type" required>@foreach(['numeric'=>'Numeric','text'=>'Text','percentage'=>'Percentage','currency'=>'Currency','index'=>'Index'] as $value=>$label)<option value="{{ $value }}" @selected(old('data_type', $row->data_type) === $value)>{{ $label }}</option>@endforeach</select></div>
            <div class="field"><label for="geography_code">Kode wilayah/entitas</label><input id="geography_code" name="geography_code" value="{{ old('geography_code', $row->geography_code) }}" required maxlength="100"></div>
            <div class="field"><label for="geography_name">Nama wilayah/entitas</label><input id="geography_name" name="geography_name" value="{{ old('geography_name', $row->geography_name) }}" required maxlength="255"></div>
            <div class="field"><label for="period">Periode</label><input id="period" name="period" value="{{ old('period', $row->period) }}" required maxlength="30"></div>
            <div class="field"><label for="source_locator">Lokasi bukti</label><input id="source_locator" name="source_locator" value="{{ old('source_locator', $row->source_locator) }}" required maxlength="255" placeholder="Halaman 24, tabel 3"></div>
            <div class="field"><label for="value_numeric">Nilai numerik</label><input id="value_numeric" name="value_numeric" type="number" step="0.000001" value="{{ old('value_numeric', $row->value_numeric) }}"><small>Isi salah satu: numerik atau teks.</small></div>
            <div class="field"><label for="value_text">Nilai teks</label><input id="value_text" name="value_text" value="{{ old('value_text', $row->value_text) }}" maxlength="10000"></div>
            <div class="field field--span-2"><label for="source_excerpt">Cuplikan bukti</label><textarea id="source_excerpt" name="source_excerpt" rows="3" maxlength="2000">{{ old('source_excerpt', $row->source_excerpt) }}</textarea></div>
            <div class="field"><label for="status">Keputusan</label><select id="status" name="status" required>@foreach(['proposed'=>'Belum diputuskan','accepted'=>'Terima','rejected'=>'Tolak'] as $value=>$label)<option value="{{ $value }}" @selected(old('status', $row->status) === $value)>{{ $label }}</option>@endforeach</select></div>
            <div class="field"><label for="admin_note">Catatan reviewer</label><textarea id="admin_note" name="admin_note" rows="3" maxlength="2000">{{ old('admin_note', $row->admin_note) }}</textarea></div>
        </div>
        <div class="form-actions"><a class="button button--line" href="{{ route('admin.ai-extractions.show', $job) }}">Batal</a><button class="button button--ink" type="submit">Simpan review</button></div>
    </form>

    <aside class="admin-rail">
        <section class="panel evidence-card"><span class="panel-kicker">Bukti sumber</span><h2>{{ $row->source_locator }}</h2><blockquote>{{ $row->source_excerpt ?: 'Model tidak memberikan cuplikan. Buka dokumen asli sebelum menerima baris ini.' }}</blockquote><a class="button button--line button--block" href="{{ route('admin.source-documents.download', $job->sourceDocument) }}">Unduh dokumen sumber</a></section>
        <section class="panel validation-card"><span class="panel-kicker">Validasi</span><h2>{{ $row->validation_status === 'warning' ? 'Peringatan' : ($row->validation_status === 'invalid' ? 'Tidak valid' : 'Valid') }}</h2>@if($row->validation_issues)<ul>@foreach($row->validation_issues as $issue)<li>{{ $issue }}</li>@endforeach</ul>@else<p>Tidak ada isu struktural terdeteksi. Kebenaran substantif tetap harus diperiksa manusia.</p>@endif<div class="confidence-line"><span>Keyakinan model</span><strong>{{ number_format((float) $row->confidence * 100, 0) }}%</strong></div></section>
    </aside>
</div>
@endsection
