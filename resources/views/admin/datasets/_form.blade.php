<div class="form-sections">
    <fieldset class="form-section">
        <legend><span>01</span><strong>Identitas katalog</strong><small>Informasi yang membantu dataset ditemukan dan dibedakan.</small></legend>
        <div class="form-grid form-grid--2">
            <div class="field field--span-2">
                <label for="title">Judul dataset</label>
                <input id="title" name="title" value="{{ old('title', $dataset->title ?? '') }}" maxlength="220" required placeholder="Contoh: Indikator Kinerja Keuangan Perusahaan 2020–2025">
            </div>
            <div class="field">
                <label for="code">Kode dataset</label>
                <input id="code" name="code" value="{{ old('code', $dataset->code ?? '') }}" maxlength="80" required placeholder="CONTOH-001">
                <small>Kode unik. Sistem menyimpannya dalam huruf kapital.</small>
            </div>
            <div class="field">
                <label for="data_provider_id">Penyedia data</label>
                <select id="data_provider_id" name="data_provider_id" required>
                    <option value="">Pilih penyedia</option>
                    @foreach($providers as $provider)
                        <option value="{{ $provider->id }}" @selected((string) old('data_provider_id', $dataset->data_provider_id ?? '') === (string) $provider->id)>{{ $provider->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field field--span-2">
                <div class="field-label-row">
                    <label for="summary">Ringkasan katalog</label>
                    <output for="summary" data-character-output="summary">0 / 1.000</output>
                </div>
                <textarea id="summary" name="summary" rows="4" maxlength="1000" required data-character-count placeholder="Dalam 2–4 kalimat, jelaskan isi, cakupan, periode, dan kegunaan dataset.">{{ old('summary', $dataset->summary ?? '') }}</textarea>
                <small>Ringkasan tampil pada kartu katalog. Teks panjang, batasan, dan konteks masuk ke Deskripsi lengkap.</small>
            </div>
            <div class="field field--span-2">
                <label for="description">Deskripsi lengkap <span>(opsional)</span></label>
                <textarea id="description" name="description" rows="7" maxlength="20000" placeholder="Jelaskan konteks, populasi, cakupan, batasan, dan cara membaca data.">{{ old('description', $dataset->description ?? '') }}</textarea>
            </div>
        </div>
    </fieldset>

    <fieldset class="form-section">
        <legend><span>02</span><strong>Cakupan dan akses</strong><small>Tentukan apa yang dicakup dataset dan bagaimana peneliti mendapatkannya.</small></legend>
        <div class="form-grid form-grid--2">
            <div class="field field--span-2">
                <label for="data_scope">Jenis cakupan data</label>
                <select id="data_scope" name="data_scope" required>
                    <option value="regional" @selected(old('data_scope', $dataset->data_scope ?? 'regional') === 'regional')>Statistik wilayah & pemerintah — BPS, kementerian, pemda</option>
                    <option value="corporate" @selected(old('data_scope', $dataset->data_scope ?? '') === 'corporate')>Perusahaan & ESG — emiten, laporan tahunan, keberlanjutan</option>
                </select>
                <small>Menentukan label entitas dan jalur pencarian di katalog publik. Data lama otomatis masuk kategori statistik wilayah.</small>
            </div>
            <div class="field">
                <label for="category">Kategori</label>
                <input id="category" name="category" value="{{ old('category', $dataset->category ?? '') }}" maxlength="100" required placeholder="Ekonomi, kesehatan, lingkungan">
            </div>
            <div class="field">
                <label for="access_type">Tipe akses</label>
                <select id="access_type" name="access_type" required>
                    <option value="open" @selected(old('access_type', $dataset->access_type ?? '') === 'open')>Terbuka — langsung unduh</option>
                    <option value="restricted" @selected(old('access_type', $dataset->access_type ?? 'restricted') === 'restricted')>Terbatas — perlu persetujuan</option>
                    <option value="commercial" @selected(old('access_type', $dataset->access_type ?? '') === 'commercial')>Berlisensi — dapat memiliki biaya</option>
                </select>
            </div>
            <div class="field">
                <label for="frequency">Frekuensi</label>
                <input id="frequency" name="frequency" value="{{ old('frequency', $dataset->frequency ?? '') }}" maxlength="50" placeholder="Tahunan, triwulanan, bulanan">
            </div>
            <div class="field">
                <label for="geographic_level">Level wilayah atau entitas data</label>
                <input id="geographic_level" name="geographic_level" value="{{ old('geographic_level', $dataset->geographic_level ?? '') }}" maxlength="80" placeholder="Nasional, provinsi, perusahaan">
            </div>
            <div class="field">
                <label for="period_start">Periode awal</label>
                <input id="period_start" name="period_start" type="number" min="1900" max="2100" value="{{ old('period_start', $dataset->period_start ?? '') }}" placeholder="2020">
            </div>
            <div class="field">
                <label for="period_end">Periode akhir</label>
                <input id="period_end" name="period_end" type="number" min="1900" max="2100" value="{{ old('period_end', $dataset->period_end ?? '') }}" placeholder="2025">
            </div>
        </div>
    </fieldset>

    <fieldset class="form-section">
        <legend><span>03</span><strong>Sumber dan metodologi</strong><small>Catatan yang dibutuhkan agar data dapat dipertanggungjawabkan.</small></legend>
        <div class="form-grid form-grid--2">
            <div class="field">
                <label for="license">Lisensi</label>
                <input id="license" name="license" value="{{ old('license', $dataset->license ?? '') }}" maxlength="120" placeholder="CC BY 4.0 atau kebijakan penyedia">
            </div>
            <div class="field">
                <label for="source_url">URL sumber resmi</label>
                <input id="source_url" name="source_url" type="url" value="{{ old('source_url', $dataset->source_url ?? '') }}" maxlength="255" placeholder="https://">
            </div>
            <div class="field field--span-2">
                <label for="methodology">Metodologi <span>(opsional saat draft)</span></label>
                <textarea id="methodology" name="methodology" rows="7" maxlength="20000" placeholder="Jelaskan pengumpulan data, definisi populasi, standardisasi, dan proses review.">{{ old('methodology', $dataset->methodology ?? '') }}</textarea>
            </div>
        </div>
    </fieldset>
</div>
