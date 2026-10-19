# 🛒 Mak Ethek (MyEthek)
> **Platform Perdagangan & Logistik Hyperlocal Berjenjang Berbasis Ekosistem Koperasi Jawa Timur**

[![Laravel](https://img.shields.io/badge/Laravel-11.x-FF2D20?style=for-the-badge&logo=laravel)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php)](https://php.net)
[![PWA Ready](https://img.shields.io/badge/PWA-Ready-5A0FC8?style=for-the-badge&logo=pwa)](https://web.dev/progressive-web-apps/)
[![License](https://img.shields.io/badge/License-MIT-blue?style=for-the-badge)](LICENSE)

---

## 📌 Ringkasan Eksekutif

**Mak Ethek** adalah platform perdagangan kebutuhan pokok dan sembako berjenjang (*hyper-local multi-tier commerce*) yang mengonsolidasikan seluruh rantai pasok **Koperasi Jawa Timur** — mulai dari level Induk Provinsi, Pusat Distribusi Kabupaten/Kota, Koperasi Unit Usaha Kecamatan, hingga Warung Kelontong di tingkat Desa/Kelurahan ke dalam satu ekosistem digital terpadu.

Tujuan utama sistem ini adalah memperpendek rantai pasok, melindungi stabilitas harga bahan pokok, serta memberdayakan warung mikro desa tanpa membebani mereka dengan birokrasi keuangan modern (seperti KYC korporat) melalui permodelan **Koperasi Financial Holding**.

---

## 🏛️ Arsitektur Hierarki 4-Tingkat & Cascading Fallback

```
[ PUSAT / INDUK ]  (Level Provinsi Jawa Timur)
       │  ▲
       ▼  │  Kulakan B2B / Distribusi Massal
[ DISTRIBUTOR ]    (Level Kabupaten / Kota)
       │  ▲
       ▼  │  Kulakan B2B / Buffer Stock
    [ AGEN ]       (Level Kecamatan)
       │  ▲
       ▼  │  Kulakan B2B & Cadangan Pengiriman Retail
  [ SUB-AGEN ]     (Warung Kelontong Desa / Kelurahan)
       │
       ▼
 [ KONSUMEN ]      (Warga Rumah Tangga) ◄── [ KURIR OJOL LOKAL ]
```

### Logika Pemilihan Toko (Cascading Fallback Pipeline)
1. **Prioritas Sub-Agen (Desa):** Pesanan warga diprioritaskan dilayani oleh warung terdekat di desa/kelurahan setempat.
2. **Evaluasi 5 Parameter:** Area jangkauan (*coverage*), status buka toko (*online*), ketersediaan stok keranjang, batas minimum belanja, dan kesiapan kurir.
3. **Otomatis Fallback ke Agen (Kecamatan):** Jika warung desa tutup atau kehabisan stok, keranjang otomatis dialihkan ke Agen di tingkat Kecamatan.
4. **Fallback ke Distributor (Kabupaten):** Jika Agen kecamatan tidak tersedia, pemenuhan diarahkan ke Pusat Distribusi Kabupaten/Kota.

---

## ✨ Fitur-Fitur Unggulan

### 🛍️ 1. Mobile-First PWA Storefront
- **Mobile-Optimized:** Desain intuitif bergaya aplikasi native belanja harian (*PWA-ready* dengan Service Worker offline caching).
- **Katalog Satuan Tradisional:** Mendukung satuan belanja lokal Indonesia (*ikat*, *250 gram*, *500 gram*, *kg*, *sak*).
- **Single-Merchant Cart Enforcement:** Keranjang belanja hanya memuat barang dari 1 toko untuk menjamin kepastian ongkir dan pengiriman.
- **Lokasi & Toko Terhubung:** Deteksi kelurahan/kecamatan otomatis dengan opsi pemilih lokasi cepat di navbar.

### 🚚 2. Hyperlocal Logistics Engine
- **Formula Jarak Haversine Darat:** Perhitungan jarak riil pedesaan dengan faktor koreksi rute darat ($1.3\times$):
  $$\text{Jarak Rute (km)} = \text{Haversine}(\text{Toko}, \text{Customer}) \times 1.3$$
- **Tarif Ongkir Transparan:** Biaya dasar terjangkau dengan tarif per kilometer tambahan yang dapat disesuaikan.
- **Milestone Tracking:** Pelacakan status realistis (`Menunggu Konfirmasi` $\rightarrow$ `Dikemas` $\rightarrow$ `Kurir Ditugaskan` $\rightarrow$ `Dalam Perjalanan` $\rightarrow$ `Selesai`) hemat baterai dan minim kuota.
- **Verifikasi Serah-Terima (POD & OTP):** Kurir mengunggah foto bukti serah terima atau konfirmasi kode OTP 4-digit.
- **Tautan WhatsApp Otomatis:** Tombol chat langsung pelanggan $\leftrightarrow$ warung $\leftrightarrow$ driver dengan template ringkasan pesanan.

### 📦 3. B2B Kulakan Berjenjang (Procurement)
- **Rantai Pasokan Tertutup:** 
  - Sub-Agen Desa kulakan ke Agen Kecamatan.
  - Agen Kecamatan kulakan ke Distributor Kota/Kabupaten.
  - Distributor kulakan ke Induk Koperasi Provinsi.
- **Tier Wholesale Pricing & MOQ:** Penetapan harga grosir berjenjang otomatis sesuai volume dan tingkatan mitra.
- **Sinkronisasi Stok Otomatis:** Penerimaan barang kulakan langsung meng-update stok etalase eceran warung.

### 💳 4. Financial Holding & Internal Ledger
- **Akun Legal Koperasi:** Gateway pembayaran (QRIS, VA Bank) dikelola holding koperasi; warung mikro terbebas dari hambatan registrasi legal perbankan.
- **Internal Double-Entry Ledger:** Pembayaran ditampung di `escrow_pending` dan otomatis dialokasikan ke dompet mitra, kurir, dan fee operasional koperasi begitu pesanan terkonfirmasi selesai.
- **Dompet Digital Mitra:** Penarikan saldo (*disbursement*) langsung dari dashboard pedagang.

---

## 👥 Matriks Akun Uji Coba (Demo Credentials)

Semua akun demo di bawah menggunakan password default: `password`

| Peran | Nama Akun | Email Login | Cakupan Wilayah |
|---|---|---|---|
| **Admin Pusat** | Admin Induk Koperasi Jatim | `admin@myethek.id` | Provinsi Jawa Timur |
| **Distributor** | Koperasi Distribusi Blitar | `distributor.blitar@myethek.id` | Kota Blitar |
| **Agen** | Koperasi Sananwetan Berkah | `agen.sananwetan@myethek.id` | Kec. Sananwetan, Blitar |
| **Sub-Agen (Warung)** | Warung Mak Ethek Gedog | `warung.gedog@myethek.id` | Kel. Gedog, Sananwetan |
| **Kurir Ojol** | Agus Driver Gedog | `driver.gedog@myethek.id` | Radius Operasional Gedog |
| **Konsumen (Sub-Agen)** | Siti Khadijah | `customer.gedog@myethek.id` | Kel. Gedog (Prioritas Warung) |
| **Konsumen (Fallback)** | Rahmat Hidayat | `customer.bendogerit@myethek.id` | Kel. Bendogerit (Tes Fallback) |

---

## 🚀 Panduan Instalasi & Menjalankan Lokal

### Prasyarat Sistem
- **PHP** $\ge$ 8.2 (dengan ekstensi `pdo_mysql`, `mbstring`, `openssl`, `curl`, `gd`, `bcmath`)
- **Composer** $\ge$ 2.5
- **Node.js** $\ge$ 18.x & **NPM**
- **MySQL** $\ge$ 8.0 atau **MariaDB** $\ge$ 10.4

### Langkah Instalasi

1. **Clone Repositori:**
   ```bash
   git clone https://github.com/NegativePeny/myethek.git
   cd myethek
   ```

2. **Install Dependensi Backend & Frontend:**
   ```bash
   composer install
   npm install
   ```

3. **Konfigurasi Environment:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Konfigurasi Database pada `.env`:**
   Sesuaikan parameter koneksi database Anda:
   ```ini
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=myethek
   DB_USERNAME=root
   DB_PASSWORD=
   ```

5. **Jalankan Migrasi & Seeder Data Uji Coba:**
   ```bash
   php artisan migrate:fresh --seed
   ```

6. **Build Aset Frontend:**
   ```bash
   npm run build
   # atau untuk mode pengembangan:
   npm run dev
   ```

7. **Jalankan Server Lokal:**
   ```bash
   php artisan serve
   ```
   Aplikasi dapat diakses melalui browser di [http://localhost:8000](http://localhost:8000).

---

## 🗺️ Struktur Navigasi Endpoint

| URL Path | Keterangan | Akses Role |
|---|---|---|
| `/` | Halaman Depan Retail (Storefront PWA) | Publik / Pelanggan |
| `/cart` | Keranjang Belanja Toko Tunggal | Publik / Pelanggan |
| `/checkout` | Proses Pembayaran & Opsi Pengiriman | Pelanggan (Login) |
| `/user/orders` | Riwayat & Tracking Pesanan Pelanggan | Pelanggan |
| `/merchant` | Dashboard Mitra Penjual & Inventori | Merchant (Sub-Agen, Agen, Distributor) |
| `/merchant/procurement` | Modul Kulakan Grosir Berjenjang (B2B) | Merchant |
| `/driver` | Portal Order & Penugasan Kurir Ojol | Driver / Kurir |
| `/admin` | Panel Audit Koperasi Induk Jawa Timur | Admin |

---

## 📂 Struktur Direktori Utama

```
myethek/
├── app/
│   ├── Http/Controllers/       # Logika aplikasi (Storefront, Cart, Checkout, Merchant, Driver, Admin)
│   └── Models/                 # Model Eloquent (Territory, Merchant, Order, Product, Wallet, Escrow, dsb.)
├── database/
│   ├── migrations/             # Struktur skema tabel hierarki wilayah, toko, ledger, order
│   └── seeders/                # Data referensi wilayah Jatim, katalog produk, dan matriks akun demo
├── public/
│   ├── grofar/                 # Aset tema mobile grocery (CSS, Icons, Fonts, JS)
│   ├── manifest.json           # Manifest Progressive Web App (PWA)
│   └── sw.js                   # Service worker caching
├── resources/
│   └── views/                  # Blade templates (storefront, auth, merchant, driver, layouts)
├── routes/
│   └── web.php                 # Rute utama sistem
└── vite.config.js              # Konfigurasi bundler Vite
```

---

## 📄 Lisensi

Hak cipta dilindungi undang-undang. Didistribusikan di bawah lisensi [MIT](LICENSE). Inisiatif pengembangan ekosistem digital Koperasi Jawa Timur.
