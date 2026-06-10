# Progress Fitur Login Biometrik (WebAuthn / Passkey)

Dokumen ini mencatat kemajuan pengembangan fitur otentikasi biometrik (sidik jari, Face ID, Windows Hello) di aplikasi `sys-modern` agar mudah dilanjutkan atau dites di kemudian hari.

## Status Saat Ini
- **Backend & Logic:** Selesai (100%).
- **Frontend & UI:** Selesai (100%).
- **Status Testing:** Menunggu pengujian (*Pending Testing*) dari sisi pengguna di environment yang menggunakan HTTPs atau localhost secara langsung.

---

## Apa Saja yang Telah Dikerjakan?

### 1. Database & Migrasi
- Telah dibuat tabel `tbluser_webauthn` untuk menyimpan *credentialId* dan *PublicKey* unik masing-masing perangkat.
- File Migration: `app/Database/Migrations/2026-06-09-075603_WebAuthnCredentials.php`
- Model: `app/Models/MWebauthnModel.php`

### 2. Library Inti
- Kita menggunakan library `lbuchs/webauthn` (via Composer) untuk meng-handle verifikasi dan pembuatan *challenge* standar FIDO2 WebAuthn.
- Format *rpId* (Relying Party ID) sudah diatur dinamis menyesuaikan nama domain/host tanpa *port* untuk mencegah *Crash (500 Server Error)*.

### 3. Backend (CodeIgniter 4 Controller)
- **Controller Baru:** `app/Controllers/Webauthn.php`
- **Route Baru (di `Routes.php`):**
  - `GET /webauthn/getRegisterArgs` (Mendapatkan challenge pendaftaran)
  - `POST /webauthn/processRegister` (Memverifikasi & menyimpan sidik jari)
  - `GET /webauthn/getLoginArgs` (Mendapatkan challenge untuk login)
  - `POST /webauthn/processLogin` (Memverifikasi sidik jari & login otomatis)
- **Filter Global:** `app/Config/Filters.php` telah disesuaikan agar endpoint `/webauthn/getLoginArgs` dan `/webauthn/processLogin` di-bypas dari pengecekan session/login `auth` filter (mengatasi *302 Redirect & Parser Error*).

### 4. Frontend & User Interface
- **Javascript Global:** `public/libraries/tas-lib/js/webauthn.js` berisi fungsi *wrapper* API `navigator.credentials.get` dan `navigator.credentials.create`.
  - Telah diperbaiki juga fungsi *decoder* untuk menangani format biner khusus dari `lbuchs` (`=?BINARY?B?...?=`).
- **Halaman Profil (Pendaftaran):** 
  - Ditambahkan section dan tombol "Daftarkan Perangkat Ini" untuk mendaftarkan HP/Laptop yang sedang dipakai ke akun tersebut.
  - File: `app/Views/profil/view.php`
- **Halaman Login (Otentikasi Depan):**
  - Ditambahkan tombol "Login Biometrik / Passkey" di bagian bawah tombol login utama.
  - Sesuai permintaan/konsep, tombol ini **otomatis disembunyikan jika diakses menggunakan PC/Desktop** (menggunakan CSS Media Query `max-width: 991.98px`). Tombol hanya terlihat jika diakses dari Mobile/Tablet.
  - File: `app/Views/login.php`

---

## Langkah Selanjutnya (Untuk Dikerjakan / Dites Nanti)

1. **Uji Coba Pendaftaran (Registration Flow)**
   - Login dengan username dan password normal dari HP/Tablet.
   - Pergi ke menu Profil, lalu klik **Daftarkan Perangkat Ini**.
   - Pastikan *popup* biometrik (Fingerprint/FaceID) muncul dan berhasil mendaftarkan perangkat.

2. **Uji Coba Login (Login Flow)**
   - Logout dari aplikasi.
   - Buka halaman login di perangkat yang sudah didaftarkan.
   - Klik tombol **Login Biometrik / Passkey**.
   - Pastikan *popup* biometrik muncul dan otomatis mengarahkan masuk ke Ruang Kerja tanpa mengisi password.

## Catatan Penting
- **HTTPS Wajib:** API `navigator.credentials` bawaan browser tidak akan berfungsi (akan *undefined*) jika web diakses tanpa enkripsi HTTPS (SSL), **kecuali** diakses murni dari `localhost` atau `127.0.0.1`.
- Jika terjadi error `500 internal server error` lagi di masa mendatang, biasanya karena variabel `$_SERVER['HTTP_HOST']` mengandung IP Address aneh atau format yang tidak didukung *Relying Party* FIDO2.
