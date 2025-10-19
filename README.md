# 🚀 Kiosk Management System: Pasar Digital Terintegrasi

---
## 🌟 Apa Itu Aplikasi Ini?
Sebuah platform full-stack untuk mengelola kios, produk, dan transaksi pasar secara digital. Dibangun dengan arsitektur terpisah (frontend-backend) untuk skalabilitas tinggi, cocok untuk pengelolaan pasar fisik *atau* virtual.

---
## 💡 Berguna Untuk Apa?
- **Pengelola Pasar**: Kelola kios, verifikasi penjual, dan monitor transaksi.
- **Penjual**: Upload produk, cek pesanan, dan kelola stok.
- **Pengunjung**: Lihat katalog produk dan detail kios secara real-time.

---
## 🗺️ Rute Utama (Frontend & Backend)
### **Frontend (Vue.js)**
| Rute                | Tujuan                                  | Akses       |
|---------------------|-----------------------------------------|-------------|
| `/`                 | Halaman utama (katalog produk)          | Guest       |
| `/product/:id`      | Detail produk                           | Guest       |
| `/kiosk/:id`        | Detail kios penjual                     | Guest       |
| `/login`            | Halaman login multi-role                | Semua       |
| `/admin/dashboard`  | Dashboard admin (statistik & manajemen) | Admin       |
| `/admin/users`      | Manajemen pengguna (admin & seller)     | Admin       |
| `/admin/kiosks`     | Manajemen data kios                     | Admin       |
| `/admin/register`   | Registrasi akun admin baru              | Admin       |
| `/seller/dashboard` | Dashboard penjual (produk & pesanan)    | Seller      |
| `/seller/products`  | Manajemen produk penjual                | Seller      |
| `/seller/orders`    | Daftar pesanan masuk                    | Seller      |
| `/seller/profile`   | Pengaturan profil kios                  | Seller      |

### **Backend (Laravel API)**
| Endpoint                | Metode | Fungsi                                  | Akses       |
|-------------------------|--------|-----------------------------------------|-------------|
| `/api/auth/login`       | POST   | Otentikasi pengguna                     | Semua       |
| `/api/auth/register`    | POST   | Registrasi pengguna baru                | Semua       |
| `/api/auth/logout`      | POST   | Logout pengguna                         | Semua       |
| `/api/products`         | GET    | Daftar semua produk                     | Guest       |
| `/api/products/:id`     | GET    | Detail produk tertentu                  | Guest       |
| `/api/kiosks`           | GET    | Daftar semua kios                       | Guest       |
| `/api/kiosks/:id`       | GET    | Detail kios tertentu                    | Guest       |
| `/api/admin/users`      | GET    | Daftar semua pengguna                   | Admin       |
| `/api/admin/users/:id`  | PUT/DELETE | Update/Hapus pengguna                 | Admin       |
| `/api/admin/kiosks`     | GET/POST | Daftar/Tambah kios                    | Admin       |
| `/api/admin/kiosks/:id` | PUT/DELETE | Update/Hapus kios                     | Admin       |
| `/api/seller/products`  | GET/POST | Daftar/Tambah produk penjual          | Seller      |
| `/api/seller/products/:id`| PUT/DELETE | Update/Hapus produk penjual           | Seller      |
| `/api/seller/orders`    | GET    | Daftar pesanan masuk untuk penjual      | Seller      |
| `/api/seller/orders/:id`| PUT    | Update status pesanan                   | Seller      |

---
## 👥 Peran Pengguna (Privilege)
- **Guest**: Lihat produk dan detail kios (tanpa login).
- **Seller**: Kelola produk, pesanan, dan profil kios (setelah verifikasi).
- **Admin (Superuser)**: Manajemen seller, pengaturan sistem, dan akses penuh ke data.

---
## 🛠️ Tech Stack (Ringkas)
- **Backend**: Laravel 10 (PHP) + MySQL
- **Frontend**: Vue.js 3 + Vite + CSS Modules
- **DevOps**: Vite (build tool) + `run-dev.bat` (auto-start server)
- **Lainnya**: Sanctum (auth), Axios (API calls)

---
## 🚀 Cara Memulai
1. Clone repo: `git clone <url-repo>`
2. Konfigurasi `.env` di backend (sesuaikan database)
3. Install dependencies:
   - Backend: `composer install`
   - Frontend: `npm install`
4. Jalankan server: `run-dev.bat` (auto-start backend + frontend)

---
## 📝 Catatan
- Rute dan fitur dapat disesuaikan sesuai kebutuhan.
- Pastikan XAMPP/MAMP berjalan untuk MySQL.

---
Made with ❤️ by [Your Name]