# PHP 8.5 — peringatan PDO MySQL

Jika terminal masih menampilkan:

```text
Constant PDO::MYSQL_ATTR_SSL_CA is deprecated since 8.5
```

buka `config/database.php` di project utama, lalu ganti setiap kemunculan:

```php
PDO::MYSQL_ATTR_SSL_CA
```

menjadi:

```php
Pdo\Mysql::ATTR_SSL_CA
```

Simpan file, lalu bersihkan cache konfigurasi:

```powershell
php artisan optimize:clear
```

Overlay ini sengaja tidak menimpa `config/database.php`, supaya konfigurasi MySQL lokal dan kredensial `.env` tetap utuh.
