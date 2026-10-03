# BacaDulu Dataset v7.6 — Admin Workflow, Login Privat, Profil Peneliti, Foto Profil, dan UX Permintaan Akses

Paket ini adalah **overlay** untuk project Laravel 12 yang sudah ada di:

```text
C:\Users\ThinkPad\Web-dataset-bacadulu
```

ZIP sengaja tidak berisi `.git`, `.env`, `vendor`, atau `node_modules`, sehingga repository dan konfigurasi lokal tetap aman.
Modul AI memakai HTTP client bawaan Laravel; tidak ada package Composer baru yang perlu dipasang.
Jika PHP 8.5 menampilkan peringatan PDO MySQL, ikuti `PHP85-PDO-FIX.md` setelah menyalin overlay.

## Perubahan utama v7

- Panel admin disusun ulang berdasarkan alur operasional: **Ambil data → Susun katalog → Tinjau kualitas → Layani pengguna → Kelola sistem**.
- Dashboard memprioritaskan pekerjaan yang menunggu keputusan, bukan sekadar statistik database.
- Halaman **Katalog variabel** global memudahkan pencarian indikator lintas koleksi, provider, dan cakupan data.
- Halaman **Pusat quality control** menggabungkan antrean sinkronisasi BPS, ekstraksi dokumen, observasi belum ditinjau, data ditandai, draft, dan permintaan akses.
- Alur ini mengambil referensi dari proses publik ESGI Dataset—anotasi, review, indeksasi, dan data siap digunakan—kemudian diperluas untuk BPS/pemerintah.
- Login peneliti dan administrator memakai halaman, controller, validasi peran, serta path yang terpisah.
- Path lama `/admin/login` tidak tersedia dan tautan admin dihapus dari login peneliti.
- Seluruh route admin memakai prefix privat `BACADULU_ADMIN_PATH` dan halaman login admin diberi `noindex`.
- Pengunjung yang membuka route admin tanpa sesi diarahkan ke login admin privat, bukan login peneliti.
- Sesi peneliti dan administrator dipisahkan menjadi guard `web` dan `admin`; membuka `/login` tidak lagi membawa pengguna ke login admin.
- Public `/login` dan `/register` hanya untuk akun peneliti, sedangkan `/{BACADULU_ADMIN_PATH}/login` hanya untuk administrator.
- Tipografi, ukuran heading, line-height, dan jarak antarkomponen dirapikan agar informasi lebih mudah dipindai.
- GSAP 3.13 disertakan sebagai asset lokal; motion tetap halus dan otomatis menghormati `prefers-reduced-motion`.
- Area pengguna tidak lagi menonjolkan istilah Dashboard: halaman utama peneliti menjadi **Profil peneliti** di `/profile`.
- Halaman profil memakai public catalogue shell seperti pengalaman ESGI, tanpa sidebar admin atau susunan dashboard.
- URL lama `/dashboard` tetap tersedia sebagai pengalihan kompatibilitas ke `/profile`.
- Profil peneliti kini mengikuti struktur akun ESGI: identitas, edit profil, akses aktif, dan riwayat permintaan data.
- Menu akun pada navbar menyediakan Profil saya, Edit profil, dan Keluar.
- Cache asset antarmuka dinaikkan ke `v7.6.0`.

Tambahkan path admin privat ke `.env` lokal/server:

```env
BACADULU_ADMIN_PATH=panel-adminbaca
```

Gunakan path yang sulit ditebak dan simpan secara internal. Contoh di atas menghasilkan login:

```text
http://127.0.0.1:8000/panel-adminbaca/login
```

Setelah mengganti path, wajib jalankan `php artisan optimize:clear`. Menyembunyikan path bukan pengganti kata sandi yang kuat, rate limit, dan pemeriksaan role; ketiganya tetap aktif.

## Struktur admin v7

| Area | Fungsi |
|---|---|
| Ringkasan kerja | Menampilkan total pekerjaan yang membutuhkan perhatian |
| Pusat quality control | Menggabungkan seluruh antrean review dan publication gate |
| Sumber data | Registry BPS, perusahaan, kementerian, dan lembaga |
| Sinkronisasi BPS | Konektor, jadwal, staging, dan riwayat WebAPI BPS |
| Dokumen & ekstraksi | Sumber privat, CSV lokal, serta ekstraksi opsional |
| Koleksi data | Metadata, cakupan, lisensi, status, dan publikasi dataset |
| Katalog variabel | Daftar indikator lintas koleksi dengan tier dan harga per sel |
| Permintaan akses | Persetujuan data terbatas atau berlisensi |
| Pengguna | Status akun peneliti |
| Audit log | Jejak aktivitas penting |

## Perubahan v6.2

- Footer publik baru dengan identitas BacaDulu, profil PT Bina Cendikia Academy, lokasi kantor, peta, kontak, tautan katalog, dan kanal sosial.
- Nomor WhatsApp footer dikendalikan melalui `.env` dan tombol disembunyikan jika nomor belum diisi.
- Peta Google diizinkan secara terbatas melalui `frame-src`; kebijakan CSP lain tetap ketat.
- Semua partial admin bernama `_form.blade.php` diganti menjadi `form.blade.php` beserta seluruh referensinya.
- Cache asset publik dinaikkan ke `v6.2.0` agar browser tidak memakai tampilan lama.

## Hotfix v6.1

- Memperbaiki error `Call to undefined method Builder::orWhereKey()` ketika admin membuka tombol **Atur** pada konektor BPS.
- Variabel konektor yang sedang nonaktif tetap ditampilkan pada halaman pengaturan agar konfigurasi lama dapat diperiksa dengan aman.
- Menambahkan tes regresi untuk halaman pengaturan konektor.

## Perubahan utama v6

- Logo BacaDulu asli menggantikan ikon huruf pada navbar publik, footer, autentikasi, sidebar admin/peneliti, favicon, dan halaman error.
- Menu **Otomatisasi data** untuk konektor WebAPI BPS per variabel.
- Sinkronisasi manual, harian, mingguan, atau bulanan melalui scheduler Laravel.
- Mode simulasi lokal tanpa token untuk menguji alur enam observasi contoh.
- Data BPS selalu masuk staging dan tidak pernah langsung menjadi data publik.
- Deteksi observasi baru, berubah, dan tidak berubah berdasarkan variabel, wilayah, serta periode.
- Validasi nilai, satuan, dimensi, perubahan metadata sumber, batas ukuran respons, dan batas jumlah baris.
- Review per baris atau massal, finalisasi transaksional, audit log, dan perlindungan nested route.
- Hasil yang diterapkan masuk sebagai `unreviewed`; dataset terbit otomatis kembali ke draft bila nilainya berubah.
- API key BPS hanya dibaca dari `.env` dan tidak dikirim ke browser.

Fondasi UI v5 tetap dipertahankan:

- Sistem visual baru mengikuti referensi BacaDulu: dasar biru muda, navy gelap, kuning keemasan, putih, dan aksen merah-oranye kecil.
- Tipografi sans-serif yang tegas, hierarki lebih rapat, serta komponen yang tidak terasa seperti template AI generik.
- Dasbor admin memiliki alur penerbitan empat tahap: sumber data, koleksi, isi & QC, lalu publikasi.
- Istilah admin dipermudah menjadi **Sumber data**, **Koleksi & variabel**, dan **Bantuan ekstraksi**.
- Filter katalog mobile dapat dibuka/tutup dan filter aktif dapat dilepas satu per satu.
- Motion lokal yang aksesibel: reveal bertahap, count-up, indikator progres halaman, dan tombol kembali ke atas.
- Pengguna yang memilih `prefers-reduced-motion` tidak dipaksa melihat animasi.
- GSAP 3.13 dipakai dari `public/assets/gsap.min.js`, bukan CDN, agar CSP tetap ketat dan aplikasi tidak bergantung jaringan eksternal saat runtime.
- Animasi hanya digunakan untuk reveal bertahap, count-up, feedback, dan transisi kecil; mode `prefers-reduced-motion` menonaktifkannya.
- Tidak ada dependency frontend atau Composer baru.

Fondasi v4 tetap dipertahankan:

- Katalog publik sekarang **variable-first**: satu hasil mewakili satu indikator, bukan satu file/dataset.
- Dua jalur data yang setara: **Perusahaan & ESG** serta **Statistik wilayah & pemerintah** untuk BPS, kementerian, dan pemda.
- Filter variabel berdasarkan kata kunci, jalur data, topik, sumber, periode, perusahaan/wilayah, dan tipe akses.
- Halaman detail variabel dengan definisi, satuan, tipe data, jumlah entitas, rentang periode, sumber, dan pratinjau.
- Pilihan dari halaman variabel dibawa ke dataset builder dan otomatis ditandai.
- Admin wajib menentukan `data_scope` ketika membuat atau mengubah dataset.
- Label impor dan quality control menyesuaikan perusahaan/emiten atau wilayah/entitas.
- Contoh CSV terpisah untuk struktur BPS/wilayah dan perusahaan/ESG.
- Data lama tidak dihapus dan otomatis dikategorikan sebagai statistik wilayah saat migrasi.

## Fitur yang sudah bekerja

- Landing page dan katalog variabel publik.
- Pencarian serta filter cakupan, kategori, provider, periode, entitas, dan tipe akses.
- Halaman detail variabel dan dataset, kamus variabel, filter preview observasi, dan metadata sumber.
- Registrasi dan login peneliti.
- Login administrator terpisah di `/{BACADULU_ADMIN_PATH}/login` dan tidak ditautkan dari halaman peneliti.
- Pengelolaan provider, dataset, variabel, dan status publikasi.
- Impor observasi dari CSV dengan validasi kode variabel.
- Registry dokumen sumber privat untuk PDF, CSV, TXT, XLS, dan XLSX dengan checksum SHA-256.
- Menu admin **Dokumen & ekstraksi** di `/{BACADULU_ADMIN_PATH}/ai-ingestion`.
- Menu admin **Sinkronisasi BPS** di `/{BACADULU_ADMIN_PATH}/automation`.
- Konektor resmi WebAPI BPS dengan staging, validasi, review manusia, penerapan ke observasi, dan riwayat sinkronisasi.
- CSV-to-staging lokal yang tetap bekerja tanpa API key.
- Ekstraksi PDF/XLS/XLSX memakai OpenAI Responses API ketika sengaja diaktifkan admin.
- Structured output, normalisasi server-side, deteksi kandidat duplikat, source locator, evidence excerpt, dan confidence.
- Review manusia per baris: edit, terima, tolak, keputusan massal, dan finalisasi transaksional.
- Hasil AI tidak pernah auto-publish: observasi selalu masuk sebagai `unreviewed`, lalu wajib melewati QC.
- Quality control observasi dengan status unreviewed, reviewed, verified, atau flagged.
- Akses data terbuka melalui ekspor CSV.
- Permintaan data terbatas atau berlisensi berdasarkan variabel, wilayah, dan periode.
- Persetujuan atau penolakan admin dengan masa berlaku akses.
- Estimasi jumlah sel dan biaya berdasarkan harga tiap variabel.
- Unduhan CSV untuk permintaan yang disetujui.
- Pengelolaan status akun pengguna.
- Audit log untuk aksi penting dan unduhan.
- Pemisahan akses berbasis peran untuk peneliti dan administrator.
- CSP serta security headers, session hardening, dan rotasi session ID saat login/logout.
- Rate limit berbasis akun/IP untuk login, registrasi, impor, permintaan akses, dan unduhan.
- Perlindungan CSV formula injection dan penolakan file impor biner/berukuran berlebihan.
- Nilai dataset terbatas/berlisensi tidak pernah dikirim ke halaman publik.
- Dataset terbit otomatis kembali ke draft saat metadata, variabel, observasi, atau impor berubah, lalu wajib melewati gate publikasi ulang.
- Empty state asli tanpa dataset/provider palsu.
- Pemisahan tegas antara situs publik, ruang peneliti, dan panel administrator; tidak ada tombol tambah/kelola dataset di konten publik.
- Form dataset bertahap dengan penghitung karakter dan pesan validasi berbahasa Indonesia.
- Pencarian katalog mencakup judul, kode, ringkasan, penyedia, serta metadata variabel.
- Perlindungan runtime untuk ekstraksi PDF, pesan timeout yang aman, deteksi job macet, dan tombol pemulihan tanpa menyentuh dokumen sumber.

## Cara memasang

### 1. Hentikan server

Tekan `Ctrl+C` pada terminal yang sedang menjalankan `php artisan serve`.

### 2. Pastikan perubahan sebelumnya aman

Di root project jalankan:

```powershell
git status
git branch backup-before-dataset-mvp
```

### 3. Ekstrak ZIP dengan posisi yang benar

Buka ZIP, lalu salin **isi di dalam ZIP** langsung ke:

```text
C:\Users\ThinkPad\Web-dataset-bacadulu
```

Folder `app`, `bootstrap`, `config`, `database`, `public`, `resources`, dan `routes` harus sejajar dengan file `artisan`.

Jangan membuat struktur seperti ini:

```text
Web-dataset-bacadulu\bacadulu-dataset-mvp\app
```

Pilih **Replace the files in the destination** ketika Windows meminta konfirmasi.

### 4. Tambahkan konfigurasi admin ke `.env`

Salin nilai dari `.env.bacadulu.example` ke bagian paling bawah `.env`:

```env
APP_NAME="BacaDulu Dataset"
FILESYSTEM_DISK=public

BACADULU_ADMIN_NAME="BacaDulu Admin"
BACADULU_ADMIN_EMAIL=admin@bacadulu.test
BACADULU_ADMIN_PASSWORD="ChangeMe123!"

BACADULU_PREVIEW_LIMIT=25
BACADULU_IMPORT_LIMIT=50000
BACADULU_IMPORT_MAX_KB=20480

# WebAPI BPS. Mode mock aman untuk tes tanpa token.
BACADULU_BPS_ENABLED=false
BPS_API_KEY=
BACADULU_BPS_MOCK=true
BACADULU_BPS_TIMEOUT=25
BACADULU_BPS_MAX_ROWS=20000
BACADULU_BPS_MAX_RESPONSE_KB=5120

# AI-assisted ingestion. CSV staging tetap dapat dipakai saat false.
BACADULU_AI_ENABLED=false
OPENAI_API_KEY=
BACADULU_AI_MODEL=gpt-4.1-mini
BACADULU_AI_MAX_KB=10240
BACADULU_AI_MAX_ROWS=1000
BACADULU_AI_TIMEOUT=180
BACADULU_AI_STALE_AFTER=300
BACADULU_AI_MAX_OUTPUT_TOKENS=12000
BACADULU_AI_PDF_DETAIL=auto

SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
```

Ganti email dan password admin **sebelum menjalankan seeder**. Untuk production, password admin wajib minimal 12 karakter dan tidak boleh memakai nilai contoh. Seeder akan berhenti jika mendeteksi kredensial default di environment production. Nilai yang mengandung spasi harus memakai tanda kutip.

Jika akun dengan email tersebut sudah pernah dibuat, seeder tidak mengganti password secara diam-diam. Untuk menyelaraskan akun lama sekaligus mengganti password dari `.env`, jalankan:

```powershell
php artisan optimize:clear
php artisan bacadulu:admin-sync --reset-password
```

Perintah tersebut juga memastikan `role=admin`, `status=active`, dan verifikasi email terisi. Password tidak pernah ditampilkan ke terminal.

Biarkan `BACADULU_AI_ENABLED=false` dan `OPENAI_API_KEY=` kosong bila belum memiliki API key. Seluruh katalog, impor CSV manual, dan **staging CSV lokal** tetap berfungsi. Untuk mengaktifkan ekstraksi PDF/XLS/XLSX, simpan key hanya pada `.env` server:

```env
BACADULU_AI_ENABLED=true
OPENAI_API_KEY="masukkan-key-server-di-sini"
```

Jangan kirim API key lewat chat, screenshot, JavaScript/browser, atau Git. Setelah mengubah `.env`, jalankan `php artisan optimize:clear`.

Untuk mencoba otomatisasi BPS tanpa token, gunakan `BACADULU_BPS_MOCK=true`. Mode ini membuat enam observasi simulasi dan menandainya jelas sebagai data uji. Jangan publikasikan angka simulasi. Setelah token resmi tersedia, gunakan:

```env
BACADULU_BPS_ENABLED=true
BPS_API_KEY="masukkan-token-bps-di-server"
BACADULU_BPS_MOCK=false
```

Token WebAPI diperoleh dari portal BPS dan hanya disimpan pada `.env` server. Satu konektor memetakan satu variabel BPS ke satu variabel katalog BacaDulu.

Tiga konfigurasi tambahan AI bersifat opsional. Jika tidak ditambahkan ke `.env`, nilai aman di atas otomatis menjadi default. `BACADULU_AI_STALE_AFTER` menentukan kapan job tanpa respons boleh dipulihkan; jangan membuatnya lebih kecil daripada timeout. `BACADULU_AI_PDF_DETAIL=auto` mengurangi beban PDF dibanding memaksa detail tinggi pada semua halaman.

Konfigurasi database yang sudah ada tetap digunakan:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=web_dataset_bacadulu
DB_USERNAME=root
DB_PASSWORD=
```

### 5. Jalankan instalasi database

```powershell
php artisan optimize:clear
php artisan migrate
php artisan db:seed
php artisan bacadulu:admin-sync --reset-password
php artisan storage:link
php artisan route:list
php artisan test
```

Perintah `php artisan migrate` menambahkan kolom `data_scope` serta tabel konektor, riwayat sinkronisasi, dan staging BPS tanpa menghapus dataset, variabel, observasi, akun, atau permintaan akses lama. Dataset yang sudah ada diberi cakupan `regional`, lalu dapat dikoreksi dari panel admin bila sebenarnya merupakan data perusahaan/ESG.

Seeder tidak membuat provider atau dataset demo. Seeder hanya membuat akun admin berdasarkan `.env` dan aman dijalankan ulang tanpa mereset password admin yang sudah ada. Test suite tambahan memeriksa security headers, isolasi role/ownership, pemutusan sesi akun nonaktif, penyamaran nilai dataset terbatas, penonaktifan provider, dan nested-resource IDOR.

### 6. Jalankan aplikasi

```powershell
php artisan serve
```

Buka:

```text
http://127.0.0.1:8000
```

Login admin:

```text
http://127.0.0.1:8000/panel-adminbaca/login
```

## Cara admin menambah data

### Jalur A — CSV manual untuk data yang sudah rapi

1. Masuk ke `/{BACADULU_ADMIN_PATH}/login` (contoh bawaan: `/panel-adminbaca/login`).
2. Buka **Sumber data** dan tambahkan lembaga/perusahaan pemilik data.
3. Buka **Koleksi & variabel**, pilih jenis cakupan **Statistik wilayah & pemerintah** atau **Perusahaan & ESG**, lengkapi metadata, lalu tambahkan kamus **Variabel**.
4. Klik **Impor CSV**, isi template, lalu impor observasi.
5. Buka **Quality control**, ubah observasi menjadi `reviewed` atau `verified`.
6. Publikasikan dataset.

Jalur ini cocok untuk data besar yang sudah berbentuk tabel. Batas impor CSV massal terpisah dari batas staging AI.

### Jalur B — Dokumen laporan dibantu AI

1. Buat provider dan dataset tujuan terlebih dahulu.
2. Buka menu **Dokumen & ekstraksi** (`/{BACADULU_ADMIN_PATH}/ai-ingestion`).
3. Pilih dataset tujuan dan unggah PDF/CSV/XLS/XLSX. File disimpan pada disk privat, bukan folder `public`.
4. Buka dokumen:
   - CSV/TXT: klik **Baca CSV ke staging**; tidak memakai API eksternal.
   - PDF/XLS/XLSX: centang persetujuan pemrosesan eksternal, lalu klik **Ekstrak ke staging**.
5. Buka hasil ekstraksi. Periksa variabel, entitas, periode, nilai, unit, source locator, evidence excerpt, confidence, dan warning.
6. Edit kandidat yang salah, lalu beri keputusan **Terima** atau **Tolak** pada semua baris.
7. Klik **Finalisasi ke dataset draft**. Variabel baru hanya dibuat untuk baris yang diterima.
8. Buka **Quality control**. Semua observasi hasil ekstraksi masuk sebagai `unreviewed`.
9. Setelah diverifikasi manusia, baru publikasikan dataset.

AI di Web-dataset-bacadulu bukan chatbot riset. Fungsinya hanya membantu admin mengubah laporan menjadi kandidat observasi yang dapat diaudit. AI tidak memiliki route untuk menerbitkan dataset.

### Jalur C — Sinkronisasi otomatis WebAPI BPS

1. Daftarkan **Badan Pusat Statistik** pada menu **Sumber data**.
2. Buat dataset dengan cakupan **Statistik wilayah & pemerintah**.
3. Tambahkan variabel tujuan beserta definisi dan satuannya.
4. Buka **Otomatisasi data**, lalu klik **Tambah konektor BPS**.
5. Isi domain, ID variabel, ID periode, dimensi turunan bila tersedia, dan jadwal.
6. Klik **Sinkronkan**. Respons masuk ke staging, bukan katalog publik.
7. Periksa baris baru, berubah, warning, atau invalid. Terima atau tolak seluruh baris.
8. Klik **Terapkan ke observasi**. Hasil masuk sebagai `unreviewed`.
9. Buka **Kontrol kualitas**, verifikasi angka terhadap sumber, lalu publikasikan dataset.

Jika variabel BPS memiliki beberapa kategori turunan, isi **ID turunan variabel**. Sistem sengaja menolak pemetaan ambigu agar dua seri data tidak saling menimpa pada wilayah dan periode yang sama.

Untuk menguji scheduler secara manual:

```powershell
php artisan bacadulu:sync-connectors
php artisan schedule:list
```

Pada production, pastikan cron Laravel menjalankan `php artisan schedule:run` setiap menit. Scheduler memeriksa konektor jatuh tempo setiap jam. Sinkronisasi terjadwal tetap berhenti di staging hingga admin menyelesaikan review.

## Kenapa cukup `php artisan serve`?

Paket v6 mengirim CSS dan JavaScript siap pakai langsung dari `public/assets`. Karena tidak ada proses bundling Vite pada overlay ini, `npm run dev` tidak diperlukan untuk menjalankan antarmuka. Cukup gunakan `php artisan serve`. Jika nanti project mulai memakai source Tailwind, Vue, React, atau bundler Vite, barulah terminal kedua untuk `npm run dev` diperlukan.

Ekstraksi OpenAI berjalan sinkron agar instalasi lokal tidak membutuhkan queue worker. Aplikasi menaikkan batas eksekusi khusus permintaan ini hingga lebih panjang daripada `BACADULU_AI_TIMEOUT`. Jika koneksi tetap terputus, job ditandai gagal secara aman. Bila proses PHP mati mendadak dan job tertinggal sebagai `processing`, halaman dokumen akan menampilkan tombol **Pulihkan proses macet** setelah batas `BACADULU_AI_STALE_AFTER` terlewati.

## Urutan tes fungsi lengkap

1. Masuk sebagai admin.
2. Tambahkan satu provider.
3. Buat dataset baru.
4. Tambahkan minimal satu variabel.
5. Uji jalur CSV manual, lalu cek observasi.
6. Aktifkan mode mock BPS, buat konektor, lalu jalankan sinkronisasi.
7. Pastikan enam baris simulasi masuk staging dan belum masuk observasi.
8. Review, terapkan, lalu pastikan hasil berstatus `unreviewed` dan dataset kembali ke draft.
9. Buka **Bantuan ekstraksi**, unggah CSV kecil, dan jalankan staging lokal.
10. Review seluruh kandidat, finalisasi, lalu pastikan hasil berstatus `unreviewed`.
11. Jika OpenAI diaktifkan, ulangi dengan satu PDF yang aman untuk diproses eksternal.
12. Buka Quality Control, periksa observasi, dan selesaikan data yang flagged/unreviewed.
13. Publikasikan dataset.
14. Buka katalog publik, cari nama variabel, lalu periksa halaman detail dan preview.
15. Daftar sebagai peneliti menggunakan email lain dan uji permintaan/unduhan data.

## Format CSV observasi

Kolom wajib:

```text
variable_code,geography_code,geography_name,period,value
```

Kolom opsional:

```text
source_reference,quality_status
```

Nilai `quality_status` yang dikenali:

```text
unreviewed
reviewed
verified
flagged
```

Gunakan titik untuk angka desimal dan jangan memakai pemisah ribuan.
Simpan CSV sebagai UTF-8; file dengan byte biner atau encoding tidak valid akan ditolak.

Untuk data BPS/wilayah:

- `geography_code` diisi kode wilayah yang stabil, misalnya `ID-JB`.
- `geography_name` diisi nama wilayah, misalnya `Jawa Barat`.
- Cantumkan tabel, publikasi, atau URL resmi BPS pada `source_reference`.

Untuk data perusahaan/ESG:

- `geography_code` dipakai sebagai kode emiten atau kode internal entitas, misalnya `BBCA`.
- `geography_name` diisi nama perusahaan.
- Cantumkan halaman laporan tahunan/keberlanjutan pada `source_reference`.

Nama kolom `geography_*` sengaja dipertahankan agar file dan database lama tetap kompatibel; UI publik dan admin menampilkan istilah sesuai jenis cakupan dataset.

Untuk staging CSV lokal, kolom opsional tambahan berikut dapat membantu pembuatan variabel baru:

```text
variable_name,variable_definition,unit,data_type
```

Staging AI dibatasi maksimal 1.000 kandidat per proses. Untuk file tabel berukuran besar, gunakan impor CSV manual agar pemrosesan lebih deterministik dan hemat biaya.

## Checklist keamanan production

Bagian ini dijalankan saat aplikasi akan dipasang pada domain HTTPS, bukan ketika masih memakai `http://127.0.0.1:8000`.

1. Gunakan HTTPS valid dan paksa seluruh HTTP menuju HTTPS pada reverse proxy/web server.
2. Pastikan `.env` production berisi konfigurasi berikut. `APP_URL` harus sama persis dengan host publik karena aplikasi menggunakannya sebagai allowlist `Host` header:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://dataset.domain-anda.id

SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
SESSION_LIFETIME=60
SESSION_EXPIRE_ON_CLOSE=true

LOG_LEVEL=warning
```

3. Pastikan `APP_KEY` sudah terisi dan tidak pernah masuk Git. Jalankan `php artisan key:generate` hanya jika key masih kosong; jangan mengganti key pada sistem yang sudah aktif tanpa rencana rotasi.
4. Jangan gunakan `php artisan serve` sebagai server production. Gunakan Nginx/Apache dengan document root mengarah hanya ke folder `public`.
5. Konfigurasikan trusted proxy secara eksplisit sesuai IP load balancer/CDN. Jangan mempercayai semua proxy tanpa alasan, karena rate limiter dan audit log mengandalkan alamat IP yang benar. Trusted host sudah dibatasi ke host pada `APP_URL` saat `APP_ENV=production`.
6. Batasi permission write hanya untuk `storage` dan `bootstrap/cache`. File aplikasi lain tidak perlu writable oleh user web server.
7. Gunakan user MySQL khusus aplikasi dengan hak hanya pada database ini; jangan gunakan akun `root`.
8. Jalankan backup database terenkripsi dan uji proses restore secara berkala.
9. Dokumen sumber AI berada di private storage. Jangan mengubah disk AI menjadi `public`; atur retention, backup, dan hak akses sesuai klasifikasi data lembaga.
10. Tambahkan antivirus/malware scanning pada pipeline upload sebelum menerima dokumen dari pihak yang tidak sepenuhnya dipercaya.
11. Batasi akun/proyek OpenAI dengan spend limit dan pisahkan key staging/production. Review kebijakan data organisasi sebelum mengirim dokumen eksternal.
12. Setelah deploy jalankan:

```powershell
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan test
```

13. Untuk sistem publik bernilai tinggi, tambahkan MFA administrator, email verification, pemindaian dependency (`composer audit`), monitoring log, serta penetration test sebelum go-live. Paket ini melakukan hardening pada level aplikasi, tetapi tidak menjanjikan keamanan absolut; hasil akhir tetap bergantung pada HTTPS, server, database, secret management, dan prosedur operasional.

## Commit setelah berhasil dites

```powershell
git add .
git commit -m "Add BPS data automation and BacaDulu branding"
git push
```

Jangan pernah menjalankan `git add .env`.
