# Progress Fitur Login Biometrik (WebAuthn / Passkey)

Dokumen ini mencatat kemajuan pengembangan fitur otentikasi biometrik (sidik jari, Face ID, Windows Hello) di aplikasi `sys-modern` agar mudah dilanjutkan atau dites di kemudian hari.

## Status Saat Ini
- **Backend & Logic:** Selesai (100%).
- **Frontend & UI:** Selesai (100%).
- **Status Testing:** Terjadi kendala saat testing di perangkat lawas (Android 8 Oreo) dan kegagalan insert di server production. **(Telah dibuatkan perbaikan)**.

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

## Log Isu & Troubleshooting

### Isu 1: Error "NotSupportedError" / "NotAllowedError" di Android Jadul (Android Oreo)
**Deskripsi:** Saat user mengklik Quick Login, browser menolak dengan alasan *The operation either timed out or was not allowed* atau *NotSupportedError*. Hal ini disebabkan karena Android 8 tidak memiliki fitur **Discoverable Credentials / Passkey / Resident Keys** secara default, sehingga menolak proses login jika disodorkan form login kosong (1-click magic). Selain itu, *authenticator* jadul tidak support pemaksaan `requireUserVerification`.
**Solusi yang Diterapkan:**
1. Mengubah opsi `requireUserVerification` menjadi `false` pada pendaftaran dan login.
2. Memodifikasi frontend agar **membaca isian username**. Pengguna Android jadul **harus mengisi username dulu**, barulah klik Quick Login. Server akan memberikan *credentialId* spesifik sehingga perangkat lama bisa merespons.

### Isu 2: Pendaftaran Sukses di Layar, Tapi "Masih Belum Bisa Terdaftar" / Tidak Tersimpan
**Deskripsi:** Muncul notifikasi "Pendaftaran berhasil" di UI Android, namun saat digunakan untuk login ternyata gagal.
**Penyebab Utama:** Tabel penampung sidik jari (`tbluser_webauthn`) **TIDAK ADA** di dalam Database Server Production SQL Server. Hal ini membuat perintah `insert` di backend gagal, dan karena CI4 di Production *DBDebug=false*, pesan gagal ini tertelan (berjalan *silent*) lalu membalas seakan-akan sukses.
**Solusi:** User / Admin harus segera mengeksekusi SQL Schema pembuatan tabel `tbluser_webauthn` di SQL Server Production.

---

## Langkah Selanjutnya (Untuk Dikerjakan / Dites Nanti)

1. **Buat Tabel `tbluser_webauthn` di Database Production**
   Jalankan query ini terlebih dahulu di production:
   ```sql
   CREATE TABLE tbluser_webauthn (
       id INT IDENTITY(1,1) PRIMARY KEY,
       userpk INT NOT NULL,
       credentialId VARCHAR(MAX) NOT NULL,
       credentialPublicKey VARCHAR(MAX) NOT NULL,
       created_at DATETIME NOT NULL
   );
   ```

2. **Uji Coba Ulang Pendaftaran**
   - Pastikan update terbaru dari Controller `Webauthn.php` dan `login.php` sudah di-pull (pastikan fix Android Oreo tidak hilang atau ter-*revert* saat proses *merge pull request* sebelumnya).
   - Lakukan registrasi sidik jari melalui menu Profil.
   - Cek database production apakah row baru berhasil masuk di tabel `tbluser_webauthn`.

3. **Uji Coba Login**
   - Di perangkat yang sudah mendukung Passkey/Discoverable (misalnya iOS terbaru atau Windows Hello): Kosongkan input form, klik Quick Login.
   - Di perangkat lawas (Android 8 / Oppo lama): Ketik *Username* pada form, lalu klik Quick Login.

## Catatan Penting
- **HTTPS Wajib:** API `navigator.credentials` bawaan browser tidak akan berfungsi (akan *undefined*) jika web diakses tanpa enkripsi HTTPS (SSL), **kecuali** diakses murni dari `localhost` atau `127.0.0.1`.
- Jika terjadi error `500 internal server error` lagi di masa mendatang, biasanya karena variabel `$_SERVER['HTTP_HOST']` mengandung IP Address aneh atau format yang tidak didukung *Relying Party* FIDO2.
