# Angkringan Backend

Backend API untuk **Aplikasi Kasir & Web Katalog Angkringan**. Dibangun dengan
Laravel 12, arsitektur API-only, autentikasi Sanctum, dan semua nominal uang
disimpan sebagai **integer Rupiah** (tanpa floating point).

Dokumentasi kontrak API lengkap tersedia di [`docs/api.md`](docs/api.md) dan tipe
TypeScript di [`docs/api-contract.ts`](docs/api-contract.ts).

---

## 1. Kebutuhan

- PHP 8.2+ (proyek ini diuji dengan PHP 8.4)
- Composer
- MySQL 8+ (default `.env` menggunakan database `angkringan`)
- Postman (untuk pengujian manual)

---

## 2. Instalasi

```bash
composer install
```

Salin environment jika `.env` belum ada:

```bash
# Linux/macOS
cp .env.example .env

# Windows PowerShell
Copy-Item .env.example .env
```

Buat application key:

```bash
php artisan key:generate
```

Buat database MySQL:

```sql
CREATE DATABASE angkringan CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Sesuaikan `.env`:

```dotenv
APP_URL=http://127.0.0.1:8000
APP_TIMEZONE=Asia/Jakarta
APP_LOCALE=id

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=angkringan
DB_USERNAME=root
DB_PASSWORD=

CORS_ALLOWED_ORIGINS=http://localhost:5173,http://localhost:3000

CASHIER_EMAIL=kasir@angkringan.test
CASHIER_PASSWORD=password
```

Jalankan migrasi dan seeder:

```bash
php artisan migrate:fresh --seed
```

Seeder akan membuat:

- 15 menu contoh (makanan dan minuman)
- 1 akun admin/kasir sesuai `CASHIER_EMAIL` dan `CASHIER_PASSWORD`

Jalankan server:

```bash
php artisan serve
```

Base URL API: `http://127.0.0.1:8000/api`

> Ganti `CASHIER_PASSWORD` sebelum digunakan di produksi. Jika `.env` diubah
> setelah seeding, jalankan ulang `php artisan db:seed --class=CashierSeeder`.

---

## 3. Akun untuk Testing

| Field | Nilai default |
|---|---|
| Email | `kasir@angkringan.test` |
| Password | `password` |
| Role | `admin` |

Akun ini dapat mengakses seluruh endpoint kasir: pembayaran, pembatalan,
daftar pesanan, biaya operasional, dan laporan laba rugi.

---

## 4. Testing dengan Postman

### 4.1 Buat Environment

Di Postman, buat environment baru bernama **Angkringan Local** dengan variabel:

| Variable | Initial Value | Current Value |
|---|---|---|
| `base_url` | `http://127.0.0.1:8000/api` | `http://127.0.0.1:8000/api` |
| `token` | *(kosong)* | *(kosong)* |
| `order_id` | *(kosong)* | *(kosong)* |

Pilih environment tersebut di kanan atas Postman.

### 4.2 Login dan Simpan Token

Buat request:

- **Method**: `POST`
- **URL**: `{{base_url}}/auth/login`
- **Body** → raw → JSON:

```json
{
  "email": "kasir@angkringan.test",
  "password": "password"
}
```

Pada tab **Tests**, tambahkan script agar token tersimpan otomatis:

```js
const response = pm.response.json();

if (response.success && response.data.token) {
  pm.environment.set("token", response.data.token);
}
```

Klik **Send**. Response sukses berisi:

```json
{
  "success": true,
  "message": "Login berhasil.",
  "data": {
    "token": "1|xxxxxxxxxxxxxxxxxxxxxxxx",
    "token_type": "Bearer",
    "user": {
      "id": 1,
      "name": "Kasir Angkringan",
      "email": "kasir@angkringan.test",
      "role": "admin"
    }
  }
}
```

### 4.3 Set Authorization untuk Endpoint Kasir

Ada dua cara:

**Cara A — per request**

Pada tab **Authorization** setiap request kasir:

- Type: `Bearer Token`
- Token: `{{token}}`

**Cara B — satu folder (disarankan)**

1. Buat folder **Kasir** di collection.
2. Klik folder → **Authorization**.
3. Pilih `Bearer Token` dan isi `{{token}}`.
4. Semua request di dalam folder otomatis memakai token.
5. Untuk request publik (`GET /products`, `POST /orders` sebagai tamu),
   ubah Authorization request menjadi **No Auth**.

### 4.4 Request Publik

#### Lihat katalog

```
GET {{base_url}}/products
```

Opsional filter kategori:

```
GET {{base_url}}/products?kategori=minuman
```

Nilai kategori: `makanan`, `minuman`.

#### Buat pesanan dari meja (tamu)

- **Method**: `POST`
- **URL**: `{{base_url}}/orders`
- **Authorization**: No Auth
- **Body** → raw → JSON:

```json
{
  "nomor_meja": "12",
  "client_request_id": "5c1f5e0e-6f2a-4a7e-9c3e-2f6b1a9d8e11",
  "catatan": "Tanpa sambal",
  "items": [
    { "product_id": 1, "qty": 2 },
    { "product_id": 5, "qty": 1 }
  ]
}
```

Sesuaikan `product_id` dengan hasil `GET /products`.

Pada tab **Tests**, simpan ID order untuk request berikutnya:

```js
const response = pm.response.json();

if (response.success && response.data.id) {
  pm.environment.set("order_id", response.data.id);
}
```

Response sukses: HTTP `201`. Response publik tidak memuat `total_hpp` maupun
`laba_kotor`.

Catatan:

- `nomor_meja` **wajib** untuk request tamu.
- `client_request_id` opsional, mencegah pesanan ganda saat tombol submit
  ditekan dua kali.
- Jika `client_request_id` sama dikirim ulang, API mengembalikan order yang
  sama dengan HTTP `200`.

### 4.5 Request Kasir

Semua request berikut memerlukan `Authorization: Bearer {{token}}`.

#### Daftar pesanan

```
GET {{base_url}}/orders?status=pending&per_page=10
```

Filter yang tersedia:

| Query | Nilai |
|---|---|
| `status` | `pending`, `paid`, `cancelled` |
| `date` | `YYYY-MM-DD` |
| `search` | nomor invoice atau nomor meja |
| `per_page` | 1–100 |

#### Detail pesanan

```
GET {{base_url}}/orders/{{order_id}}
```

#### Bayar pesanan

- **Method**: `POST`
- **URL**: `{{base_url}}/orders/{{order_id}}/pay`
- **Body** → raw → JSON:

```json
{
  "metode_pembayaran": "cash"
}
```

Nilai valid: `cash` atau `qris`.

Response sukses akan mengubah status menjadi `paid`, mengisi `paid_at`, dan
mengunci `total_hpp`, `total_omset`, serta `laba_kotor`.

Jika order sudah dibayar, API mengembalikan HTTP `409`:

```json
{
  "success": false,
  "message": "Pesanan INV-20261022-0001 berstatus paid dan tidak dapat dibayar.",
  "code": "INVALID_ORDER_STATE"
}
```

#### Batalkan pesanan

- **Method**: `POST`
- **URL**: `{{base_url}}/orders/{{order_id}}/cancel`
- Tanpa body.

Hanya order `pending` yang bisa dibatalkan. Stok dikembalikan tepat satu kali.
Order `paid` tidak dapat dibatalkan melalui endpoint ini.

#### Catat biaya operasional

- **Method**: `POST`
- **URL**: `{{base_url}}/operational-costs`
- **Body** → raw → JSON:

```json
{
  "tanggal": "2026-10-22",
  "kategori": "arang",
  "keterangan": "Beli arang 2 karung",
  "nominal": 25000
}
```

Daftar biaya operasional:

```
GET {{base_url}}/operational-costs?start_date=2026-10-01&end_date=2026-10-31
```

#### Laporan laba rugi

```
GET {{base_url}}/reports/profit-loss?start_date=2026-10-01&end_date=2026-10-31&group_by=day
```

- `group_by=day` untuk rincian harian.
- `group_by=month` untuk rincian bulanan.
- Maksimal rentang 366 hari.
- Hanya order `paid` yang dihitung, berdasarkan `paid_at`.

Contoh response ringkas:

```json
{
  "success": true,
  "message": "Laporan laba rugi.",
  "data": {
    "periode": {
      "start_date": "2026-10-01",
      "end_date": "2026-10-31",
      "group_by": "day",
      "timezone": "Asia/Jakarta"
    },
    "ringkasan": {
      "jumlah_transaksi": 2,
      "total_omset": 150000,
      "total_hpp": 90000,
      "laba_kotor": 60000,
      "total_biaya_operasional": 20000,
      "laba_bersih": 40000
    },
    "rincian": []
  }
}
```

#### Logout

- **Method**: `POST`
- **URL**: `{{base_url}}/auth/logout`
- **Authorization**: Bearer `{{token}}`

Endpoint ini menghapus token yang sedang dipakai. Setelah logout, request kasir
akan menghasilkan HTTP `401`.

### 4.6 Skenario Pengujian yang Disarankan

| Skenario | Ekspektasi |
|---|---|
| Login dengan password salah | `422`, error pada field `email` |
| Akses endpoint kasir tanpa token | `401`, code `UNAUTHENTICATED` |
| Buat order tamu tanpa `nomor_meja` | `422`, error pada field `nomor_meja` |
| Buat order dengan qty melebihi stok | `422`, code `INSUFFICIENT_STOCK` |
| Buat order dengan `product_id` tidak ada | `422`, validasi `items.*.product_id` |
| Kirim ulang `client_request_id` yang sama | `200`, invoice sama, stok tidak berkurang dua kali |
| Bayar order `pending` | `200`, status `paid`, `paid_at` terisi |
| Bayar order yang sudah `paid` | `409`, code `INVALID_ORDER_STATE` |
| Batalkan order `pending` | `200`, stok kembali |
| Batalkan order yang sudah dibatalkan | `409`, stok tidak bertambah dua kali |
| Batalkan order `paid` | `409` |
| Laporan dengan `end_date` sebelum `start_date` | `422` |
| Laporan rentang lebih dari 366 hari | `422` |

Urutan pengujian end-to-end yang disarankan:

1. `GET /products` → catat `product_id`.
2. `POST /orders` (No Auth, isi `nomor_meja`) → otomatis simpan `order_id`.
3. `POST /auth/login` → otomatis simpan `token`.
4. `POST /orders/{order_id}/pay` → cek status `paid`.
5. `POST /operational-costs` → catat biaya hari ini.
6. `GET /reports/profit-loss` → cocokkan total omset, HPP, laba kotor, dan
   laba bersih.
7. Ulangi `POST /orders` lalu `POST /orders/{order_id}/cancel` → cek stok
   kembali melalui `GET /products`.

### 4.7 Tips Postman

- Gunakan **Collection Runner** dengan urutan folder: publik → login → kasir.
- Simpan `order_id` dan `token` sebagai environment variable agar tidak perlu
  copy-paste.
- Pastikan header `Accept: application/json` aktif. API tetap memaksa response
  JSON untuk path `api/*`, tetapi header ini membantu saat debugging.
- Jika mendapat `401` padahal sudah login, periksa kembali tab Authorization
  dan pastikan token `{{token}}` tidak kosong.
- Jika mendapat `429`, tunggu satu menit. Endpoint login dibatasi 5 request per
  menit dan endpoint publik dibatasi 30–60 request per menit.

---

## 5. Menjalankan Automated Test

Test menggunakan SQLite in-memory sehingga tidak menyentuh database MySQL lokal:

```bash
php artisan test
```

Menjalankan test tertentu:

```bash
php artisan test --filter=CreateOrderTest
php artisan test --filter=ProfitLossReportTest
```

Cakupan test:

- Autentikasi dan logout
- Pembuatan order, validasi stok, rollback, dan idempotensi
- Pembayaran, penguncian total, dan pencegahan pembayaran ganda
- Pembatalan serta pengembalian stok
- Daftar pesanan dan filter
- Katalog produk tanpa membocorkan HPP
- Biaya operasional
- Laporan laba kotor/bersih harian dan bulanan

Cek format kode:

```bash
vendor/bin/pint --test
```

---

## 6. Aturan Finansial Singkat

- Semua nominal adalah integer Rupiah.
- Harga jual dan HPP di-snapshot ke `order_items` saat order dibuat.
- Stok dipotong saat order dibuat dan dikembalikan saat order `pending`
  dibatalkan.
- `laba_kotor = total_omset - total_hpp`.
- `laba_bersih = laba_kotor - total_biaya_operasional`.
- Laporan hanya menghitung order `paid` berdasarkan `paid_at`, dengan batas
  tanggal mengikuti timezone `Asia/Jakarta`.
