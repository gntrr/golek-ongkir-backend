## Golek Ongkir Backend — Laravel API

REST API sederhana berbasis Laravel untuk pencarian lokasi (provinsi, kota, kecamatan), perhitungan ongkos kirim domestik, dan pelacakan resi. API ini menjadi adaptor ke layanan pihak ketiga bergaya RajaOngkir (default: Komerce) dan menambahkan caching serta validasi.

—

## Tech stack

- PHP 8.2+
- Laravel 12.x

## Fitur

- Endpoint publik tanpa autentikasi untuk: provinces, cities, districts, search, cost, track
- Rate limiting bawaan: 60 request/menit per IP
- Caching hasil (provinces/cities/districts/search/cost) untuk mengurangi hit ke upstream

## Persiapan & jalankan lokal

1) Salin env dan isi variabel yang diperlukan

```bat
copy .env.example .env
```

2) Install dependency dan generate APP_KEY

```bat
composer install
php artisan key:generate
```

3) Konfigurasi .env (lihat bagian Konfigurasi) lalu jalankan server

```bat
php artisan serve
```

API akan tersedia di http://127.0.0.1:8000 (endpoint berada di prefix /api).

Catatan: Proyek menyertakan SQLite untuk kebutuhan dasar Laravel, tetapi modul API ini tidak bergantung pada database.

## Konfigurasi

Setel variabel berikut di file `.env`:

- RAJAONGKIR_KEY: kunci API provider upstream (wajib)
- RAJAONGKIR_BASE: base URL upstream (opsional, default `https://rajaongkir.komerce.id/api/v1`)

Opsional terkait performa:

- CACHE_DRIVER=file|redis|… (default file). Data tertentu di-cache: provinces (1 hari), cities (1 hari), districts (1 hari), search (30 menit), cost (10 menit).

## Kontrak respons

Semua endpoint mengembalikan struktur standar dari upstream yang dibungkus:

```json
{
	"error": false,
	"data": { /* payload upstream */ }
}
```

Jika upstream gagal, bentuknya:

```json
{
	"error": true,
	"status": 4xx/5xx,
	"message": "penjelasan kesalahan dari upstream"
}
```

Kesalahan validasi dari API ini mengikuti standar Laravel (HTTP 422) dengan payload `errors` per field.

## Rate limiting

Semua rute berada di belakang throttle `60,1` (maksimal 60 request per menit per IP). Jika terlampaui akan menerima HTTP 429.

## API Reference

Base URL lokal: `http://127.0.0.1:8000/api`

### GET /provinces

Mengambil daftar provinsi.

Contoh:

```bash
curl -X GET "http://127.0.0.1:8000/api/provinces"
```

### GET /cities?province={id}

Mengambil daftar kota berdasarkan `province` (integer, required).

Contoh:

```bash
curl -G "http://127.0.0.1:8000/api/cities" --data-urlencode "province=5"
```

Validasi: `province` wajib, integer.

### GET /districts?city={id}

Mengambil daftar kecamatan berdasarkan `city` (integer, required).

Contoh:

```bash
curl -G "http://127.0.0.1:8000/api/districts" --data-urlencode "city=501"
```

Validasi: `city` wajib, integer.

### GET /search?search={term}

Pencarian langsung tujuan domestik. Parameter query dapat memakai `search`, `keyword`, atau `q` (akan dipetakan ke `search`). Minimal 2 karakter.

Contoh:

```bash
curl -G "http://127.0.0.1:8000/api/search" --data-urlencode "q=jakarta selatan"
```

Validasi: `search` wajib, string, min 2.

### POST /cost

Hitung ongkir domestik. Menerima JSON atau form-encoded body.

Body fields:

- origin (int, required): ID kecamatan asal
- destination (int, required): ID kecamatan tujuan
- weight (int, required): berat dalam gram, min:1
- couriers (string, required): daftar kurir dipisah koma, contoh: "jne,pos,tiki"

Contoh (JSON):

```bash
curl -X POST "http://127.0.0.1:8000/api/cost" \
	-H "Content-Type: application/json" \
	-d "{\n    \"origin\": 5473,\n    \"destination\": 6302,\n    \"weight\": 1200,\n    \"couriers\": \"jne,pos,tiki\"\n  }"
```

Caching: kombinasi parameter di-cache 10 menit.

### POST /track

Lacak resi. `last_phone_number` hanya relevan untuk kurir `jne` (5 digit terakhir nomor telepon penerima pada beberapa skenario).

Body fields:

- courier (string, required)
- waybill (string, required)
- last_phone_number (string, optional; khusus jne)

Contoh:

```bash
curl -X POST "http://127.0.0.1:8000/api/track" \
	-H "Content-Type: application/json" \
	-d "{\n    \"courier\": \"jne\",\n    \"waybill\": \"ABCDEFG12345\",\n    \"last_phone_number\": \"12345\"\n  }"
```

## CORS

Konfigurasi CORS tersedia di `config/cors.php`. Untuk konsumsi dari front-end lain domain, pastikan origin/headers/metode diizinkan sesuai kebutuhan.

## Pengujian

Jalankan test:

```bat
composer test
```

## Deployment singkat

- Pastikan variabel `.env` terisi (APP_KEY, RAJAONGKIR_KEY, dsb.)
- Aktifkan cache konfigurasi/route untuk performa:

```bat
php artisan config:cache
php artisan route:cache
```

## Lisensi

Proyek ini dirilis di bawah lisensi MIT. Lihat file [LICENSE](./LICENSE) untuk detail lengkapnya.

