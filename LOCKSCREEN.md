# Dokumentasi Fitur Lockscreen (Idle Session Timeout)

Fitur Lockscreen (Kunci Layar) dirancang untuk mengamankan data pengguna ketika aplikasi dibiarkan terbuka tanpa aktivitas (*idle*) selama durasi tertentu (standar: 15 menit). Fitur ini telah sepenuhnya diadaptasi ke dalam lingkungan CodeIgniter 4 (CI4) dengan integrasi keamanan tingkat lanjut berupa *Cross-Tab Synchronization* (sinkronisasi lintas tab).

## 1. Arsitektur & Skema Sistem

Sistem ini berdiri di atas tiga pilar utama:
1. **Frontend Logic (`lockscreen.js`)**: Sebagai otak sensor aktivitas yang mengatur *timer*, membaca/menulis memori *localStorage*, dan menangkap *events*.
2. **Overlay UI (`footer.php`)**: Kerangka visual (DOM) berupa *modal fullscreen* statis yang disembunyikan (*hidden*) dan dipanggil secara dinamis oleh JS.
3. **Backend API (`Login.php`)**: *Endpoint* yang ditugaskan secara eksklusif untuk memverifikasi ulang *password* milik *user* aktif.

### Alur Kerja (Workflow)
*   **Aktivitas**: Setiap kali *user* menggerakkan *mouse*, melakukan klik, mengetik, atau *scroll*, waktu aktivitas (*timestamp*) dicatat.
*   **Idle Detection**: Sebuah interval berjalan setiap detik untuk mengecek apakah `Waktu Sekarang - Waktu Aktivitas > 15 Menit`.
*   **Trigger Lock**: Jika batas waktu terlampaui, UI Overlay dikembangkan (`fadeIn`), fokus kursor dipaksa pindah ke kolom *password*, dan interaksi ke layar belakang (latar) diblokir total.
*   **Sinkronisasi Tab (BroadcastChannel & LocalStorage)**: 
    * Jika *Tab A* aktif, maka *Tab B* (yang sedang tidak dilihat) **tidak akan terkunci** karena keduanya membagikan (*share*) data waktu aktivitas dari `localStorage`.
    * Jika *user* salah memasukkan *password* di *Tab 1*, penghitung (*failed attempts*) di *Tab 2* ikut bertambah secara *real-time*. Jika batas (3 kali gagal) terpenuhi di salah satu tab, **semua tab** otomatis dikeluarkan (*logout*).
    * Jika *user* berhasil melakukan *unlock* di *Tab 1*, sinyal pembukaan (*Unlock Signal*) dikirim via `BroadcastChannel` agar *Tab 2*, *Tab 3*, dst ikut menghapuskan gembok layarnya secara serentak tanpa perlu memuat ulang (*reload*) halaman.

## 2. Rincian Berkas (Files)

### A. Javascript Global (`public/libraries/tas-lib/js/lockscreen.js`)
File ini dimuat pada keseluruhan halaman (hanya jika *user* sudah *login*). 
**Fitur Utama**:
- *Variable Constants*: `IDLE_TIMEOUT` (durasi tunggu), `LOCKED_KEY` (penanda layar terkunci), `LAST_ACTIVITY_KEY` (waktu aktivitas terakhir), `FAILED_ATTEMPTS_KEY` (jumlah gagal).
- Menyadap 6 *event listeners* utama: `mousemove, mousedown, keydown, wheel, touchstart, scroll`.
- Melakukan *throttling* (*rate-limiting* ke *localStorage*) sebanyak maks 1 *write* per detik untuk menghemat memori *browser*.

### B. View Template (`app/Views/partials/footer.php`)
Menyimpan HTML Modal tepat sebelum tag `</body>`.
**Karakteristik**:
- `style="z-index: 10050; position: fixed; inset: 0;"`: Memastikan layarnya berada di tingkat lapisan tertinggi di seluruh elemen AdminLTE maupun grid jqGrid.
- `backdrop-filter: blur(5px)`: Memburamkan visual di belakang layar sehingga data rahasia tidak bisa dibaca orang saat ditinggal ke toilet/beristirahat.

### C. Controller Auth (`app/Controllers/Login.php`)
Memiliki satu buah *method* API berbasis AJAX:
```php
public function unlock() { ... }
```
**Logika**:
- Menerima POST request `password`.
- Mengambil identitas `userid` langsung dari perlindungan `session()`. Ini mencegah manipulasi/injeksi untuk meng- *unlock* menggunakan *username* orang lain.
- Jika validasi dengan `MloginModel` tervalidasi, mengembalikan `JSON {success: true}`. Jika salah, mengembalikan kegagalan untuk ditangkap oleh penghitung UI.

## 3. Fitur Keamanan

- **Anti-Bypass Fokus Kursor**: `document.activeElement.blur()` dipanggil saat gembok turun, mencegah alat *keyboard-macro* tanpa sengaja berinteraksi dengan elemen aplikasi yang tak kasat mata.
- **Fail-Safe Max Attempts**: *User* yang melakukan interupsi *brute-force* pada *password* akan dikunci habis dan ditendang (*force logout*) pada percobaan ke-3. Angka jumlah gagal ini persisten meskipun halaman ditutup dan dibuka lagi (lewat perantaraan *localStorage*).
- **Session Hijacking Prevention**: Karena *password* divalidasi langsung ke *database* di bawah otorisasi sesi saat itu, gembok layar memastikan bahwasanya orang yang duduk kembali ke meja komputer adalah pemilik asli akun tersebut.
