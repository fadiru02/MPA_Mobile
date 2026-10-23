# API Angkringan

Dokumentasi singkat backend API Kasir & Web Katalog Angkringan.
Semua nominal uang adalah **integer Rupiah**. Tidak ada nilai desimal.

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve
```

Environment penting:

```dotenv
APP_TIMEZONE=Asia/Jakarta
CORS_ALLOWED_ORIGINS=http://localhost:5173,http://localhost:3000
CASHIER_EMAIL=kasir@angkringan.test
CASHIER_PASSWORD=password
```

Seeder membuat menu contoh dan satu akun admin/kasir sesuai `CASHIER_EMAIL`
dan `CASHIER_PASSWORD`. Ganti password tersebut sebelum dipakai di produksi.

## Format Response

Sukses:

```json
{
  "success": true,
  "message": "Pesan.",
  "data": {}
}
```

Gagal:

```json
{
  "success": false,
  "message": "Pesan kesalahan.",
  "code": "ERROR_CODE",
  "errors": {
    "field": ["Pesan validasi."]
  }
}
```

Daftar pesanan memakai pagination:

```json
{
  "success": true,
  "message": "Daftar pesanan.",
  "data": {
    "data": [],
    "links": {},
    "meta": {}
  }
}
```

## Autentikasi

`POST /api/auth/login`

```json
{
  "email": "kasir@angkringan.test",
  "password": "password"
}
```

Gunakan header berikut untuk endpoint kasir:

```
Authorization: Bearer <token>
```

Endpoint `POST /api/auth/logout` menghapus token yang sedang dipakai.
`GET /api/auth/me` mengembalikan data pengguna.

## Endpoint Publik

### `GET /api/products`

Query opsional: `kategori=makanan|minuman`.

Response tidak pernah memuat `harga_modal`.

### `POST /api/orders`

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

- `client_request_id` opsional, berguna untuk mencegah double-submit. Request
  dengan ID yang sama mengembalikan order yang sudah ada (HTTP 200).
- `nomor_meja` wajib untuk tamu. Kasir terautentikasi boleh mengosongkannya.
- Harga, HPP, dan total dihitung dari data produk di server.
- Response publik tidak memuat `total_hpp` dan `laba_kotor`.

Status: `201` order baru, `200` duplikat `client_request_id`, `422` stok
kurang/produk tidak aktif/validasi gagal.

## Endpoint Kasir

| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/orders` | Filter `status`, `date`, `search`, `per_page` |
| GET | `/api/orders/{order}` | Detail order |
| POST | `/api/orders/{order}/pay` | Bayar order `pending` |
| POST | `/api/orders/{order}/cancel` | Batalkan order `pending` |
| GET | `/api/operational-costs` | Filter `start_date`, `end_date` |
| POST | `/api/operational-costs` | Catat biaya operasional |
| GET | `/api/reports/profit-loss` | Laporan laba rugi |

### Pembayaran

```json
{ "metode_pembayaran": "cash" }
```

Nilai yang valid: `cash`, `qris`. Order yang sudah `paid` atau `cancelled`
ditolak dengan HTTP `409` dan kode `INVALID_ORDER_STATE`.

### Pembatalan

Hanya order `pending` yang dapat dibatalkan. Stok dikembalikan tepat satu kali
di dalam transaksi yang sama.

### Biaya Operasional

```json
{
  "tanggal": "2026-10-22",
  "kategori": "arang",
  "keterangan": "Beli arang 2 karung",
  "nominal": 25000
}
```

### Laporan Laba Rugi

```
GET /api/reports/profit-loss
    ?start_date=2026-10-01
    &end_date=2026-10-31
    &group_by=day
```

- `group_by`: `day` atau `month`.
- Hanya order berstatus `paid` yang dihitung, berdasarkan `paid_at`.
- `laba_kotor = total_omset - total_hpp`.
- `laba_bersih = laba_kotor - total_biaya_operasional`.
- Rentang maksimal 366 hari.

## Catatan Finansial

- Harga jual dan HPP disalin ke `order_items` saat order dibuat.
- Pembayaran menghitung ulang total dari salinan tersebut, bukan harga produk
  terkini, lalu mengunci `paid_at`.
- Stok dipotong saat order dibuat dan dikembalikan saat order `pending`
  dibatalkan.
- Tipe TypeScript untuk kontrak API tersedia di `docs/api-contract.ts`.
