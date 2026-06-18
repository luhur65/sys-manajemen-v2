# SYS-MODERN (Sistem Manajemen V2)

**SYS-MODERN** adalah aplikasi *Enterprise Resource Planning* (ERP) dan Sistem Manajemen terintegrasi generasi kedua yang dikembangkan dengan *framework* **CodeIgniter 4 (CI4)**. Aplikasi ini dirancang untuk menangani beban data yang masif dengan performa tinggi, memberikan antarmuka bergaya Excel yang familier bagi pengguna operasional, serta menerapkan standar keamanan tingkat lanjut.

## 🚀 Fitur Unggulan

### 1. Grid Data Super Cepat & Cerdas (jqGrid + IndexedDB)
*   **Lazy Loading Monolith**: Menggunakan arsitektur *lazy loading* khusus (`lazyLoadingGridMonolith.js`) yang mampu merender puluhan ribu baris data secara instan tanpa membebani memori browser.
*   **Silent Background Prefetching**: Aplikasi secara otomatis mengunduh halaman data berikutnya (halaman 2, 3, dst.) ke dalam **IndexedDB** saat pengguna sedang melihat halaman 1. Hasilnya, navigasi antar-halaman terasa seketika (0 detik waktu tunggu).
*   **Excel-Like Navigation**: Pengguna dapat melakukan navigasi antar sel dan baris (Atas, Bawah, Kiri, Kanan, *Page Down*) menggunakan *keyboard* persis seperti Microsoft Excel. Sel aktif (`activeColumnIndex`) akan terus disinkronisasi bahkan saat berpindah halaman.

### 2. Keamanan & Autentikasi Canggih
*   **Lockscreen Anti-Idle Berbasis Lintas-Tab**: Jika pengguna tidak ada aktivitas selama 15 menit, layar akan otomatis terkunci. Menggunakan `BroadcastChannel` dan `localStorage`, sehingga saat pengguna meng-*unlock* satu tab, semua tab lainnya yang terbuka akan ikut terbuka secara magis. Memiliki batas maksimal 3 kali salah *password* sebelum *auto-logout*.
*   **Dukungan WebAuthn**: Memungkinkan login menggunakan otentikasi biometrik modern (Sidik Jari / *FaceID*) tanpa *password*.
*   **ACL & Role Management Dinamis**: Sistem hak akses (ACL) terperinci untuk mengontrol siapa saja yang berhak menambah, mengubah, atau menghapus data di menu tertentu, dengan fitur *bypass* khusus untuk level *Admin*.

### 3. Antarmuka Pengguna (UI/UX)
*   **AdminLTE 3.x**: *Template* responsif berbasis Bootstrap 4.
*   **Theme Switcher (Dark/Light Mode)**: Tersedia mode gelap dan terang berbasis *jQuery UI* (Darkhive & Cupertino) yang dapat diubah secara dinamis dan tersimpan di memori browser.
*   **Komponen Input Lanjutan**:
    *   *AutoNumeric*: Format mata uang yang presisi.
    *   *Inputmask*: Validasi input tanggal, nomor telepon, dsb.
    *   *Select2*: Dropdown pencarian dengan integrasi AJAX.
    *   *MonthPicker / YearPicker*: Pilihan periode laporan yang intuitif.

---

## 🏗️ Arsitektur Teknologi

### Backend (Server-Side)
*   **Framework**: CodeIgniter 4 (PHP 8.x)
*   **Database**: MySQL / MariaDB / SQL Server (via abstraksi model)
*   **Struktur MVC**: Pemisahan yang ketat antara Model, View, dan Controller untuk kemudahan pemeliharaan (*maintenance*).

### Frontend (Client-Side)
*   **Javascript Library**: jQuery 3.x
*   **Data Grid**: jqGrid 5.7.0 (Bootstrap 4 Edition)
*   **State & Storage**: 
    *   *IndexedDB*: Untuk *caching* & *prefetching* data API.
    *   *LocalStorage*: Untuk menyimpan preferensi *user* (tema warna) dan sinkronisasi status *idle*.
    *   *BroadcastChannel API*: Komunikasi *real-time* antar tab.

---

## 📂 Struktur Direktori Penting

*   `app/Controllers/` - Memuat logika bisnis dan *endpoints* AJAX (contoh: `Login.php`, `Piutangemkl.php`).
*   `app/Views/` - Memuat berkas antarmuka (*views*).
    *   `partials/` - Komponen *reusable* seperti `header.php`, `footer.php`, `sidebar.php`.
*   `public/libraries/tas-lib/js/` - Berisi *engine* utama Javascript aplikasi:
    *   `mains.js` - Mengatur inisialisasi menu, *bindkeys*, dan fungsionalitas global.
    *   `lazyLoadingGridMonolith.js` - Otak pemrosesan *grid* jutaan baris & manajemen IndexedDB.
    *   `lockscreen.js` - Pengatur sesi *idle* dan keamanan *cross-tab*.
*   `app/Config/Routes.php` - Pengaturan *routing* aplikasi (Pencocokan RESTful / Explicit Routing).

---

## 💡 Referensi Dokumentasi
Untuk memahami secara teknis bagaimana fitur-fitur kompleks di dalam sistem ini dibangun, Anda dapat membaca dokumentasi yang terlampir di direktori akar (*root*):
1.  **`LOCKSCREEN.md`** - Detail arsitektur keamanan *idle-timeout* dan *cross-tab sync*.
2.  **`catatan_acl_bypass.md`** - Referensi logika otorisasi *Role-Based Access Control* (RBAC).
3.  **`dokumentasi_excel_active_cell.md`** - Aturan navigasi bergaya Excel di dalam jqGrid.

---

*Dikembangkan untuk memberikan skalabilitas, kecepatan, dan pengalaman pengguna tingkat tinggi di ekosistem ERP modern.*
