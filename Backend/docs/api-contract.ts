/**
 * Kontrak API Aplikasi Kasir & Katalog Angkringan.
 *
 * Semua nominal uang menggunakan integer Rupiah. Jangan mengirim atau
 * memproses nilai desimal untuk field bertipe Rupiah.
 */

export type Rupiah = number;

export type UserRole = 'admin' | 'cashier';
export type PaymentStatus = 'pending' | 'paid' | 'cancelled';
export type PaymentMethod = 'cash' | 'qris';
export type ProductCategory = 'makanan' | 'minuman';
export type ReportGroupBy = 'day' | 'month';

export interface ApiSuccess<T> {
  success: true;
  message: string;
  data: T;
}

export interface ApiError {
  success: false;
  message: string;
  code?: string;
  errors?: Record<string, string[]>;
}

export type ApiResponse<T> = ApiSuccess<T> | ApiError;

export interface PaginationLink {
  url: string | null;
  label: string;
  active: boolean;
}

export interface PaginationMeta {
  current_page: number;
  from: number | null;
  last_page: number;
  links: PaginationLink[];
  path: string;
  per_page: number;
  to: number | null;
  total: number;
}

export interface Paginated<T> {
  data: T[];
  links: {
    first: string | null;
    last: string | null;
    prev: string | null;
    next: string | null;
  };
  meta: PaginationMeta;
}

export interface User {
  id: number;
  name: string;
  email: string;
  role: UserRole;
}

export interface LoginResponse {
  token: string;
  token_type: 'Bearer';
  user: User;
}

export interface Product {
  id: number;
  nama: string;
  kategori: ProductCategory;
  stok: number;
  harga_jual: Rupiah;
  is_active: boolean;
}

export interface PublicOrderItem {
  id: number;
  product_id: number;
  nama_produk: string;
  qty: number;
  harga_satuan: Rupiah;
  subtotal: Rupiah;
}

export interface OrderItem extends PublicOrderItem {
  harga_modal_satuan: Rupiah;
  subtotal_hpp: Rupiah;
}

export interface PublicOrder {
  id: number;
  nomor_invoice: string;
  nomor_meja: string | null;
  status_pembayaran: PaymentStatus;
  catatan: string | null;
  total_omset: Rupiah;
  items: PublicOrderItem[];
  created_at: string | null;
}

export interface Order {
  id: number;
  nomor_invoice: string;
  nomor_meja: string | null;
  status_pembayaran: PaymentStatus;
  metode_pembayaran: PaymentMethod | null;
  total_hpp: Rupiah;
  total_omset: Rupiah;
  laba_kotor: Rupiah;
  catatan: string | null;
  items: OrderItem[];
  paid_at: string | null;
  cancelled_at: string | null;
  created_at: string | null;
  updated_at: string | null;
}

export interface OperationalCost {
  id: number;
  tanggal: string;
  kategori: string;
  keterangan: string | null;
  nominal: Rupiah;
  created_at: string | null;
}

export interface CreateOrderRequest {
  nomor_meja?: string;
  client_request_id?: string;
  catatan?: string;
  items: Array<{
    product_id: number;
    qty: number;
  }>;
}

export interface PayOrderRequest {
  metode_pembayaran: PaymentMethod;
}

export interface CreateOperationalCostRequest {
  tanggal: string;
  kategori: string;
  keterangan?: string;
  nominal: Rupiah;
}

export interface ProfitLossSummary {
  jumlah_transaksi: number;
  total_omset: Rupiah;
  total_hpp: Rupiah;
  laba_kotor: Rupiah;
  total_biaya_operasional: Rupiah;
  laba_bersih: Rupiah;
}

export interface ProfitLossRow extends ProfitLossSummary {
  periode: string;
}

export interface ProfitLossReport {
  periode: {
    start_date: string;
    end_date: string;
    group_by: ReportGroupBy;
    timezone: string;
  };
  ringkasan: ProfitLossSummary;
  rincian: ProfitLossRow[];
}
