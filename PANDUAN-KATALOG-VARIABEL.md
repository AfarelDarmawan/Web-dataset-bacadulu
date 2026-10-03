# Panduan singkat katalog variabel BacaDulu

## Konsep yang dipakai

Pengunjung tidak perlu menebak isi sebuah file. Mereka mencari **variabel** seperti `Jumlah Penduduk`, `ROA`, atau `Emisi Scope 1`. Setiap variabel tetap berada di dalam satu dataset agar sumber, metode, lisensi, dan proses aksesnya jelas.

Ada dua jenis cakupan:

1. **Statistik wilayah & pemerintah** — data BPS, kementerian, pemda, provinsi, kabupaten/kota, atau tingkat nasional.
2. **Perusahaan & ESG** — data emiten/perusahaan, laporan keuangan, laporan tahunan, atau laporan keberlanjutan.

## Yang dilakukan admin

1. Buka menu **Sumber data** dan daftarkan BPS, kementerian, BEI, atau perusahaan pemilik data.
2. Buka **Koleksi & variabel**, buat dataset, lalu pilih jenis cakupannya.
3. Tambahkan **variabel** lengkap dengan kode, definisi, satuan, tipe, serta tingkat akses.
4. Masukkan **observasi** melalui sinkronisasi WebAPI BPS, CSV manual, staging CSV lokal, atau ekstraksi dokumen berbantuan AI.
5. Periksa setiap observasi di **Quality control** dan ubah status menjadi `reviewed` atau `verified`.
6. Publikasikan dataset. Variabel yang mempunyai observasi lolos QC otomatis muncul di katalog publik.

## Arti dataset, variabel, dan observasi

- **Dataset** adalah wadah dan konteks sumber, misalnya “Indikator Kependudukan Provinsi 2020–2025”.
- **Variabel** adalah indikator yang dicari pengguna, misalnya “Jumlah Penduduk”.
- **Observasi** adalah satu nilai variabel untuk satu perusahaan/wilayah dan satu periode.

Contoh BPS: `Jumlah Penduduk × Jawa Barat × 2024 = nilai`.

Contoh perusahaan: `Emisi Scope 1 × PT Contoh Tbk × 2024 = nilai`.

## Otomatisasi BPS

Untuk data BPS, admin tidak perlu mengetik angka satu per satu. Buat satu konektor untuk satu variabel katalog, tentukan domain, ID variabel, periode, dimensi turunan, dan jadwal. Sistem mengambil respons WebAPI ke staging serta membedakan baris baru, berubah, atau tetap.

Admin hanya memeriksa warning dan perubahan, lalu menerima atau menolak staging. Baris yang diterapkan tetap masuk sebagai `unreviewed` dan belum dapat dipublikasikan sebelum quality control selesai. Mode mock tersedia untuk menguji alur tanpa token BPS, tetapi angkanya hanya data simulasi.

## Aturan publikasi

Variabel hanya tampil bila dataset berstatus `published`, penyedia aktif, variabel aktif, dan tersedia minimal satu observasi `reviewed` atau `verified`. Nilai data restricted/commercial tidak dimasukkan ke HTML publik. AI hanya membuat kandidat dan tidak dapat mempublikasikan data secara otomatis.

## Langkah setelah menyalin overlay v6

```powershell
php artisan optimize:clear
php artisan migrate
php artisan test
php artisan serve
```

Tambahkan konfigurasi `BACADULU_BPS_*` dan `BPS_API_KEY` dari `.env.bacadulu.example` tanpa menimpa nilai `.env` yang sudah ada. Jangan memasukkan `.env` ke Git. Asset antarmuka sudah berada di `public/assets`, jadi tidak perlu menjalankan `npm run dev`.
