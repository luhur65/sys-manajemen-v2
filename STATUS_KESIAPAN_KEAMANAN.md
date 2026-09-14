# Dokumentasi Keamanan sys-modern — Pekerjaan yang Sudah Selesai

**Untuk:** developer yang mengerjakan, meninjau, atau melanjutkan keamanan aplikasi ini
**Acuan standar:** `SecurityAgent.md` (Coding Standard Internal)
**Sumber temuan:** `LAPORAN_AUDIT_KEAMANAN.md` — 28 temuan (C/H/M/L)
**Diverifikasi:** 1 September 2026 pada HEAD `2da7b88`
**Dasar verifikasi:** pembacaan langsung source code + 206 test method di `tests/unit/`

> **Cakupan dokumen ini — baca ini dulu.**
> Isinya **hanya pekerjaan keamanan yang sudah selesai dikerjakan dan sudah aktif di kode**.
> Temuan yang belum disentuh sama sekali **tidak dicantumkan di sini**; daftar lengkap 28
> temuan beserta yang masih terbuka ada di `LAPORAN_AUDIT_KEAMANAN.md`.
> Dokumen ini bukan laporan status proyek — ini dokumentasi teknis: apa yang sudah
> terpasang, di berkas mana, aturan pakainya, dan uji apa yang menguncinya.

---

## 0. Cara membaca tanda

| Tanda | Arti | Boleh dianggap beres? |
|---|---|---|
| ✅ **TUNTAS** | Selesai penuh. Tidak ada sisa pekerjaan pada temuan ini | Ya — cukup jaga jangan sampai regresi |
| ◐ **SEBAGIAN** | Bagian yang dikerjakan sudah jadi dan aktif, tetapi masih ada sisa yang tercatat | Belum — lihat §11 |
| 🟢 **RISIKO DITERIMA** | Implementasi selesai; jalur lama sengaja dibiarkan terbuka dengan kontrol pengimbang dan batas waktu | Belum — keputusan sadar, terjadwal |

**Yang dimaksud "hanya selesai" pada dokumen ini adalah baris bertanda ✅ TUNTAS.**
Baris ◐ dan 🟢 tetap dicantumkan karena kodenya sudah ada dan harus dipelihara, tetapi
**bukan** pekerjaan yang boleh dicoret dari daftar.

---

## 1. Ringkasan Pekerjaan yang Selesai

| Tanda | Jumlah | Kode temuan |
|---|---|---|
| ✅ TUNTAS | **12** | C-01, C-02, C-03, C-04, C-05, H-01, H-03, H-04, H-05, M-05, L-01, L-05 |
| ◐ SEBAGIAN | **4** | M-01, M-02, M-07, M-09 |
| 🟢 RISIKO DITERIMA | **1** | H-07 |
| **Total ada pekerjaan** | **17 dari 28** | 11 temuan sisanya belum dikerjakan — tidak dibahas di sini |

| Severity | ✅ Tuntas | ◐ Sebagian | 🟢 Risiko diterima |
|---|---|---|---|
| Critical | 5 dari 5 | — | — |
| High | 4 dari 8 | — | 1 |
| Medium | 1 dari 9 | 4 | — |
| Low / Info | 2 dari 6 | — | — |

**Seluruh temuan Critical sudah tuntas.**

---

## 2. Temuan Critical yang Sudah Ditutup

| Kode | Temuan | Tanda | Tanggal | Implementasi | Uji regresi |
|---|---|---|---|---|---|
| **C-01** | SQL Injection pada filter grid (`operationAll`) | ✅ **TUNTAS** | 22 Agu 2026 | [`GridFilter`](app/Libraries/GridFilter.php) — whitelist nama kolom milik controller + whitelist operator + escaping oleh driver DB yang mengeksekusi. Dipakai lewat `BaseController::gridFilter()` di 28 controller/model | — *(belum ada test khusus)* |
| **C-02** | ACL tidak pernah dieksekusi | ✅ **TUNTAS** | 20 Agu 2026 | [`AclFilter`](app/Filters/AclFilter.php) terdaftar global di [Filters.php](app/Config/Filters.php); pencocokan memakai class/method hasil router, bukan bentuk string URL | `AclFilterTest`, `AclTest` |
| **C-03** | CSRF dinonaktifkan global | ✅ **TUNTAS** | 22 Agu 2026 | Filter `csrf` aktif dan **diurutkan sebelum `auth`**; token disebar lewat `<meta name="csrf-token">` + `$.ajaxSetup` di [header.php:8](app/Views/partials/header.php:8) | `CsrfTest`, `CsrfRenderTest`, `CsrfClientCoverageTest` |
| **C-04** | Bypass autentikasi via regex di `AuthFilter` | ✅ **TUNTAS** | 21 Agu 2026 | Pencocokan pola URL dihapus dari [`AuthFilter`](app/Filters/AuthFilter.php); pengecualian dipindah ke daftar `except` berisi route eksplisit | — *(dijaga tidak langsung oleh `AclFilterTest`)* |
| **C-05** | Skrip debug `public/test_*.php` dapat diakses publik | ✅ **TUNTAS** | 24 Agu 2026 | Artefak lokal, tidak ikut rilis. `.gitignore` menutup `/public/test_*.php` dan `test_*.php`; `public/test_param.php` sudah tidak tracked (verifikasi 31 Agu 2026) | — |

---

## 3. Temuan High yang Sudah Ditutup

| Kode | Temuan | Tanda | Tanggal | Implementasi | Uji regresi |
|---|---|---|---|---|---|
| **H-01** | Session fixation | ✅ **TUNTAS** | 22 Agu 2026 | `session()->regenerate(true)` pada **seluruh** jalur login: [Login.php:264](app/Controllers/Login.php:264), [Login.php:387](app/Controllers/Login.php:387), [SsoAuth.php:186](app/Controllers/SsoAuth.php:186), [Webauthn.php:344](app/Controllers/Webauthn.php:344) | `SessionFixationTest` |
| **H-03** | SQL Injection pada `ORDER BY` (`sidx`/`sord`) | ✅ **TUNTAS** | 22 Agu 2026 | [`GridSort`](app/Libraries/GridSort.php) di **26 model** — identifier ketat (`Kolom` / `tabel.Kolom`), whitelist `$sortable`, limit/offset di-cast integer | `GridSortTest`, `GridSortBehaviourTest`, `GridSortCoverageTest` |
| **H-04** | Tanpa rate limiting / anti brute force | ✅ **TUNTAS** | 24 Agu 2026 | [`LoginThrottle`](app/Libraries/LoginThrottle.php) pada login, unlock, dan forgot-password. Dua ember: per-akun ketat (5/15 mnt) + per-IP longgar (20/15 mnt); jatah **hanya berkurang saat gagal** | `LoginThrottleTest`, `LoginThrottleEndpointTest` |
| **H-05** | Hash password disimpan di session | ✅ **TUNTAS** | 29 Agu 2026 | Dihapus dari 4 titik pembangun sesi (termasuk SSO & WebAuthn); `Profil::editpassword` dan `MloginModel::cek` membaca ulang lewat `userpk` | `PasswordHashNotInSessionTest` |
| **H-07** | Hash MD5 legacy masih diterima | 🟢 **RISIKO DITERIMA** | 31 Agu 2026 | Sengaja dibuka sebagai jalur migrasi MD5→bcrypt yang berjalan otomatis saat user login. **Tidak ada jalur penulisan password yang menghasilkan MD5 baru**, jadi populasi MD5 hanya menyusut | — |

---

## 4. Temuan Medium & Low yang Sudah Ditutup

| Kode | Temuan | Tanda | Tanggal | Implementasi | Uji regresi |
|---|---|---|---|---|---|
| **M-05** | Host header injection pada `baseURL` | ✅ **TUNTAS** | 31 Agu 2026 | Daftar putih host di [App.php:239](app/Config/App.php:239) dan [App.php:300](app/Config/App.php:300); host yang tidak lolos [pemeriksaan](app/Config/App.php:354) tidak pernah menyentuh `baseURL` — jatuh ke `app.baseURL` statis | `HostHeaderInjectionTest`, `BaseUrlFolderTest` |
| **L-01** | `tokenRandomize = false` (BREACH) | ✅ **TUNTAS** | — | [Security.php:27](app/Config/Security.php:27) `tokenRandomize = true`. `regenerate = false` **disengaja** — rotasi per-request memecahkan seluruh grid dan CRUD | `CsrfTest` |
| **L-05** | `.gitignore` belum menutup berkas scratch/debug | ✅ **TUNTAS** | 31 Agu 2026 | Pola scratch, `test_*.php`, `.env`, dan `cekmenu.php` sudah masuk `.gitignore`; `cekmenu.php` dan `test_param.php` tidak lagi tracked | — |
| **M-01** | Header keamanan belum lengkap; CSP lemah | ◐ **SEBAGIAN** | 31 Agu 2026 | 7 header di [`SecurityHeaders`](app/Filters/SecurityHeaders.php): HSTS, X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy (11 fitur), COOP `same-origin-allow-popups`, CORP `same-origin`. CSP: `base-uri`, `frame-ancestors`, `object-src` di [ContentSecurityPolicy.php](app/Config/ContentSecurityPolicy.php) | `SecurityHeadersTest` |
| **M-02** | Username enumeration di fitur lupa password | ◐ **SEBAGIAN** | 31 Agu 2026 | Satu respons seragam untuk seluruh hasil — [`Login::forgotPasswordResponse()`](app/Controllers/Login.php:86); jatah rate limit ikut dipotong untuk username tak terdaftar sehingga tidak menjadi kanal bocor | `ForgotPasswordEnumerationTest` |
| **M-07** | Audit trail tidak memadai | ◐ **SEBAGIAN** | 29 Agu 2026 | Mekanisme terpusat: [`MlogModel::saveLog()`](app/Models/MlogModel.php:79) berbasis peristiwa (`DATA_CREATE`/`DATA_UPDATE`/`DATA_DELETE`) + kolom `event`/`context`, dibuka lewat [`BaseController::auditLog()`](app/Controllers/BaseController.php:108). Login gagal, logout, dan peristiwa autentikasi sudah tercatat | `AuditTrailTest`, `AuditUserTest` |
| **M-09** | Output tanpa escaping (XSS) | ◐ **SEBAGIAN** | 31 Agu 2026 | `json_encode()` + `JSON_HEX_*` untuk konteks JS, `esc()` dan `esc(…, 'attr')` untuk HTML & atribut, escaping di titik perakitan untuk HTML yang dirakit di PHP. Menutup reflected XSS (`useracl?userpk=`) dan stored XSS lewat menu sidebar. Cakupan: **35 dari 56 berkas view** | `ViewEscapingTest` |

---

## 5. Komponen Keamanan yang Terpasang — Peta Berkas

Referensi cepat: kalau menyentuh salah satu area ini, pakai komponen yang sudah ada,
jangan menulis ulang.

| Komponen | Berkas | Perannya |
|---|---|---|
| `GridFilter` | [app/Libraries/GridFilter.php](app/Libraries/GridFilter.php) | Parser filter jqGrid aman injeksi — whitelist kolom, whitelist operator, escape lewat driver |
| `GridSort` | [app/Libraries/GridSort.php](app/Libraries/GridSort.php) | Sanitasi `ORDER BY` dan paging untuk grid |
| `LoginThrottle` | [app/Libraries/LoginThrottle.php](app/Libraries/LoginThrottle.php) | Rate limit endpoint autentikasi (token bucket di atas `service('throttler')`) |
| `AuditUser` | [app/Libraries/AuditUser.php](app/Libraries/AuditUser.php) | Penentu isi `modifiedby` dan pemisahan aktor vs penyetuju (Panel Casting) |
| `MyAuth` | [app/Libraries/MyAuth.php](app/Libraries/MyAuth.php) | Sumber keputusan ACL (`tblacos` / `tblacl` / `tbluseracl` / `tbluserroles`) |
| `AuthFilter` | [app/Filters/AuthFilter.php](app/Filters/AuthFilter.php) | Penjaga sesi login + respons 401 AJAX bertanda `sessionExpired` |
| `AclFilter` | [app/Filters/AclFilter.php](app/Filters/AclFilter.php) | Penegak ACL per class/method; mode diatur `security.aclEnforce` di `.env` |
| `SecurityHeaders` | [app/Filters/SecurityHeaders.php](app/Filters/SecurityHeaders.php) | 7 header keamanan pada seluruh respons (filter `after` global) |
| Urutan filter | [app/Config/Filters.php](app/Config/Filters.php) | `csrf` → `auth` → `acl`, plus `secureheaders` pada `after` |
| Whitelist host | [app/Config/App.php](app/Config/App.php) | `baseURL` hanya dibentuk dari host yang lolos daftar putih |

---

## 6. Aturan yang Tidak Boleh Dilanggar

Delapan hal berikut adalah invarian. Melanggarnya membuka kembali temuan yang sudah
ditutup, dan sebagian **tidak akan terlihat sebagai error** — hanya sebagai lubang.

| # | Aturan | Kalau dilanggar |
|---|---|---|
| 1 | `GridFilter` harus di-escape dengan **koneksi yang benar-benar mengeksekusi query**. Aplikasi ini memakai grup `default` **dan** `dbtruck`; SQL Server menggandakan kutip, MySQL memakai backslash | Celah SQL Injection C-01 terbuka lagi |
| 2 | Nilai `$default` pada `GridSort` **selalu** tulisan developer, tidak boleh diisi dari request | Injeksi lewat `ORDER BY` (H-03) |
| 3 | Urutan filter `csrf` **sebelum** `auth` **sebelum** `acl` | POST tanpa token menyentuh logika sebelum ditolak; ACL dievaluasi tanpa identitas |
| 4 | **Jangan** menambahkan `publickey-credentials-get=()` ke `Permissions-Policy` | Login biometrik WebAuthn mati — terlihat seperti pengetatan biasa |
| 5 | **Jangan** memasang `Cross-Origin-Embedder-Policy: require-corp` sebelum seluruh aset CDN di-host lokal | Skrip, font, dan gambar (cdnjs, Highcharts, Google Fonts, S3) diblokir sekaligus |
| 6 | `Security::$regenerate` tetap `false` | Token berputar per-request → seluruh grid dan CRUD mendadak 403 |
| 7 | Pada `LoginThrottle`, `retryAfter()` hanya mengintip (`cost = 0`); jatah berkurang **hanya** lewat `hit()` saat gagal | Satu kantor di balik satu IP publik (NAT) ikut terkunci walau semua login berhasil |
| 8 | Setiap jalur login baru **wajib** memanggil `session()->regenerate(true)`, dan **tidak boleh** menyimpan hash password ke session | Session fixation (H-01) / hash bocor lewat session (H-05) |

---

## 7. Checklist untuk Kode Baru

Saat menambah controller, model, atau view, empat hal di bawah ini sudah menjadi standar —
bukan tambahan opsional.

| Menambah… | Yang wajib dipakai |
|---|---|
| Endpoint grid baru | `BaseController::gridFilter()` + `GridSort` dengan `$sortable` yang eksplisit |
| Controller baru | Terdaftar di ACL (`tblacos`) — filter `acl` menolak class/method yang tidak dikenal |
| Aksi pengubah data | `BaseController::auditLog()` dengan konstanta `MlogModel::DATA_*` dan konteks nilai lama/baru |
| Output ke view | `esc()` untuk HTML dan atribut, `json_encode()` + `JSON_HEX_*` untuk konteks `<script>` |

---

## 8. Uji Regresi yang Mengunci Pekerjaan Ini

Total **206 test method** di `tests/unit/`. Yang berkaitan langsung dengan temuan:

| Berkas uji | Mengunci | Temuan |
|---|---|---|
| `CsrfTest`, `CsrfRenderTest`, `CsrfClientCoverageTest` | Filter aktif + seluruh pola pengiriman (header, form field, body JSON) tetap jalan; token tidak berputar per-request | C-03, L-01 |
| `AclFilterTest`, `AclTest` | Filter benar-benar terpasang dan menghasilkan 403 yang utuh, bukan fatal error | C-02 |
| `SessionFixationTest` | Regenerasi session ID di seluruh jalur login | H-01 |
| `GridSortTest`, `GridSortBehaviourTest`, `GridSortCoverageTest` | Validasi identifier, whitelist, dan cakupan 26 model | H-03 |
| `LoginThrottleTest`, `LoginThrottleEndpointTest` | Perilaku dua ember dan endpoint yang dibatasi | H-04 |
| `PasswordHashNotInSessionTest` | Tidak ada hash password yang masuk session | H-05 |
| `SecurityHeadersTest` | Kelengkapan dan **nilai** tiap header, termasuk COEP yang sengaja absen | M-01 |
| `ForgotPasswordEnumerationTest` | Respons seragam untuk seluruh hasil lupa password | M-02 |
| `HostHeaderInjectionTest`, `BaseUrlFolderTest` | Host asing tidak membentuk `baseURL`; bentuk `baseURL` per environment | M-05 |
| `AuditTrailTest`, `AuditUserTest` | `changes()` hanya menyisakan kolom yang berubah; `modifiedby` benar termasuk pada Panel Casting | M-07 |
| `ViewEscapingTest` | Escaping sesuai konteks (JS ≠ HTML) pada titik keluar berinput mentah | M-09 |

**Belum ada uji regresi khusus untuk:** C-01 (`GridFilter`), C-04 (daftar `except`), C-05, L-05.

---

## 9. Core Keamanan yang Sudah Berstatus SIAP

Hanya core yang **sudah memenuhi standar sepenuhnya**. Core yang masih sebagian atau belum
dikerjakan tidak dicantumkan di sini.

| No | Core | Dasar penilaian |
|---|---|---|
| 4 | CSRF Protection | Filter aktif dan diurutkan sebelum `auth`; token dipasang otomatis pada seluruh AJAX |
| 6 | SQL Injection Defense | Filter dan pengurutan grid dipusatkan ke library dengan whitelist + escaping driver |
| 8 | Authorization / ACL | ACL database ditegakkan di lapisan filter untuk seluruh route |
| 10 | Input Validation | Validation framework dipakai konsisten, whitelist diterapkan |
| 12 | Error Handling | Debug mode nonaktif di produksi, tidak ada kebocoran detail teknis |
| 16 | Dependency Security | Seluruh dependensi mutakhir, tanpa advisory aktif |
| 7 | File Upload | **N/A** — aplikasi tidak memiliki fitur upload file |

---

## 10. Modul Aplikasi yang Sudah Berstatus SIAP

| No | Modul | Dasar penilaian |
|---|---|---|
| 2 | WebAuthn / MFA | Implementasi lengkap dan aman; regenerasi sesi ikut diterapkan |
| 6 | User, Roles & ACL | Dilindungi penegakan ACL dan CSRF |
| 9 | Menu & Sidebar | Didukung kontrol akses sebenarnya di lapisan filter, bukan sekadar rendering |
| 10 | Skrip Debug di `public/` | C-05 ditutup: artefak lokal, tidak ikut rilis, tidak lagi tracked di Git |

Modul lain masih berstatus sebagian atau belum diaudit — lihat `LAPORAN_AUDIT_KEAMANAN.md`.

---

## 11. Batas "Selesai" pada Item ◐ dan 🟢

Bagian ini ada supaya tanda ◐ dan 🟢 tidak terbaca sebagai tuntas. Yang tercantum hanya
**sisa pekerjaan pada item yang kodenya sudah ada**.

| Kode | Yang sudah selesai | Sisa yang belum |
|---|---|---|
| M-01 | 7 header terpasang; `base-uri` / `frame-ancestors` / `object-src` di CSP | COEP `require-corp` (prasyarat: host aset CDN secara lokal); pelepasan `unsafe-inline` / `unsafe-eval` (prasyarat: pindahkan 47 blok skrip inline) |
| M-02 | Respons dan jatah rate limit seragam untuk seluruh hasil | Selisih waktu respons antara username terdaftar dan tidak terdaftar |
| M-07 | Mekanisme terpusat + pencatatan peristiwa autentikasi | `auditLog()` belum dipanggil satu pun controller CRUD (0 dari 45 controller) |
| M-09 | 35 dari 56 berkas view sudah di-escape sesuai konteks | 21 berkas view sisanya perlu penyisiran |
| H-07 | Migrasi MD5→bcrypt otomatis saat login; tidak ada jalur penulisan MD5 baru | Reset paksa akun dorman + penutupan jalur MD5 pada batas waktu (usulan 30 Nov 2026) |

---

## 12. Cara Verifikasi Ulang

Jalankan seluruh uji regresi:

```bash
composer test
```

Periksa urutan filter global dan daftar `except`:

```bash
grep -n "csrf\|'auth'\|'acl'\|secureheaders" app/Config/Filters.php
```

Periksa header yang benar-benar dikirim aplikasi:

```bash
grep -n "setHeader" app/Filters/SecurityHeaders.php
```

Pastikan tidak ada berkas berisi kredensial yang kembali ter-track:

```bash
git ls-files | grep -iE "cekmenu|test_.*\.php|\.env$"
```

---

## 13. Catatan Pemeliharaan

- Kemajuan terbesar datang dari **pemusatan logika ke library** (filter grid, pengurutan,
  throttling, audit, header). Pendekatan ini menutup temuan lintas modul sekaligus — dan
  sebaliknya, satu perubahan di library berdampak ke seluruh modul, jadi uji regresi pada
  §8 adalah pengamannya.
- Beberapa keputusan pada dokumen ini **sengaja menyimpang dari saran laporan audit**
  (COEP tidak dipasang, COOP memakai `same-origin-allow-popups`, `regenerate = false`,
  `publickey-credentials-get` tidak didaftarkan). Alasannya ditulis sebagai komentar di
  berkas terkait — baca komentarnya sebelum "merapikan" baris-baris itu.
- Status pada dokumen ini mencerminkan HEAD `2da7b88`. Perbarui setiap kali satu temuan
  berpindah ke ✅ TUNTAS, dan pindahkan sisa pekerjaannya keluar dari §11.
