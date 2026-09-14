# Laporan Implementasi Single Sign-On (SSO) — sys-modern

**Target:** `D:\php-project\sys-modern` — CodeIgniter 4.7 / PHP 8.2 / SQL Server
**Server SSO:** `auth-sso` (Next.js) + `auth-sso-api` (NestJS) — `D:\project-next\sso`, `D:\project-next\ssoapi`
**Acuan rancangan:** `sso/docs/plans/2026-07-14-sso-signed-jwt-ticket-design.md`
**Tanggal:** 26 Agustus 2026
**Status:** Terimplementasi & lulus uji otomatis. **Belum diverifikasi ujung-ke-ujung secara live.**

---

## 1. Ringkasan Eksekutif

sys-modern kini dapat menerima login dari SSO perusahaan, sederajat dengan HR dan
CRM. Pengguna login sekali di dashboard auth-sso, menekan kartu **SYS**, dan
masuk ke sys-modern tanpa mengetik kredensial lagi.

| Aspek | Hasil |
|---|---|
| Model SSO | Signed-ticket **RS256**, bukan OIDC/SAML |
| Verifikasi tiket | **Lokal** — tanpa round-trip ke server SSO, tanpa tulisan DB |
| Dependency baru | **Nol** — memakai `ext-openssl` bawaan PHP |
| Perubahan skema database | **Tidak ada** |
| Berkas baru | 6 kelas aplikasi + 5 berkas test + fixture kunci |
| Berkas diubah | 10 |
| Test | 47 test khusus SSO, seluruh suite 203 lulus |
| Single Logout | Aktif (polling introspect, cache 60 detik) |
| Login lokal | Tetap hidup berdampingan; dapat dimatikan lewat satu saklar `.env` |

**Tiga hal yang menentukan keamanan implementasi ini** — dan tidak boleh
disederhanakan oleh siapa pun nanti:

1. **Algoritma dipatok ke RS256.** Tanpa ini, tiket palsu `alg: none` atau HS256
   yang ditandatangani memakai public key sebagai secret akan lolos.
2. **`aud` diperiksa.** Tanpa ini, tiket sah milik HR atau CRM bisa diputar
   ulang di sys-modern.
3. **`jti` dibakar sekali pakai secara atomik.** Tanpa ini, tiket yang tertinggal
   di history browser atau log proxy bisa dipakai berkali-kali selama ia belum
   kedaluwarsa.

---

## 2. Cara Kerja SSO Ini

Ini **bukan** OIDC dan **bukan** SAML. Polanya *signed ticket*: server SSO
menerbitkan JWT berumur sangat pendek yang ditandatangani kunci privat RSA, dan
aplikasi anggota memverifikasinya sendiri memakai kunci publik.

```
┌──────────┐   1. login (Google/Yahoo/password)
│ Pengguna │──────────────────────────────────► auth-sso  (dashboard)
└──────────┘                                        │
     │        2. klik kartu SYS                     │
     │─────────────────────────────────────────────►│
     │                                              │  3. POST /auth/generate-ticket
     │                                              │     { appCode: "sys" }  + Bearer
     │                                              ▼
     │                                        auth-sso-api
     │                                              │  • aplikasi terdaftar?
     │                                              │  • access_menu memuat "SYS"?
     │                                              │  • sesi SSO masih hidup?
     │                                              │  • tanda tangani JWT RS256
     │        4. redirect + ?ticket=<JWT>           │
     │◄─────────────────────────────────────────────┘
     ▼
  sys-modern  GET /auth/sso-callback?ticket=…
     │  5. verifikasi tanda tangan LOKAL (public key)
     │  6. bakar `jti`  → tiket jadi sekali pakai
     │  7. cocokkan `email` ke satu baris tbluser
     │  8. regenerate session id, buat sesi sys-modern
     ▼
  redirect ke /home — sudah login
```

Langkah 5–8 tidak menyentuh jaringan sama sekali. Identitas ada **di dalam**
tiket dan dibuktikan tanda tangannya, jadi tidak perlu bertanya balik ke server
SSO. Inilah alasan rancangan ini dipilih menggantikan tiket buram sebelumnya:
satu hop layanan dan tiga operasi database hilang dari jalur kritis.

### Anatomi tiket

Diterbitkan `auth-sso-api` pada `auth.service.ts :: generateTicket()`:

| Klaim | Isi | Diperiksa sys-modern |
|---|---|---|
| `sub` | id pengguna di database SSO | wajib ada, tidak kosong |
| `aud` | kode aplikasi tujuan — untuk kita `sys` | **wajib = `sys`** |
| `iss` | `auth-sso` | **wajib = `auth-sso`** |
| `jti` | nonce acak 128 bit | wajib ada, lalu **dibakar** |
| `exp` | umur tiket (`TICKET_TTL_SECONDS`, saat ini 60 detik) | **wajib ada**, belum lewat |
| `iat`, `nbf` | waktu terbit / mulai berlaku | diperiksa bila ada |
| `email` | identitas pengguna | dicocokkan ke `tbluser.email` |
| `name` | nama tampilan | tidak dipakai |
| `karyawanId` | id karyawan (dipakai CRM) | tidak dipakai — `tbluser` tidak punya kolomnya |
| `sid` | id sesi SSO | disimpan di sesi untuk Single Logout |

---

## 3. Cara Membuat — Langkah demi Langkah

Urutan ini berlaku untuk aplikasi CI4 mana pun yang ingin bergabung ke SSO,
bukan hanya sys-modern.

### Langkah 1 — Daftarkan aplikasi di sisi SSO

Tanpa langkah ini `generateTicket()` menolak dengan "Aplikasi tidak ditemukan".

| Berkas | Perubahan |
|---|---|
| `ssoapi/src/modules/auth/auth.service.ts` | Tambah entri ke `TICKET_RELAY_APPS`, mis. `sys: { name: 'SYS', directory: 'unlinked' }` |
| `sso/constants/apps.ts` | Kartu aplikasi diberi `launch: true` dan `href` ke url callback |
| `sso/.env` | `NEXT_PUBLIC_<APP>_URL = <base url aplikasi>/auth/sso-callback` |

`name` dipakai memeriksa `access_menu` pengguna di sisi SSO, jadi harus sama
persis dengan yang ditulis admin HR pada kolom itu.

`directory` menentukan apakah auth-sso-api ikut memeriksa keberadaan akun di
database aplikasi sebelum menerbitkan tiket. sys-modern memakai `'unlinked'`
karena `tbluser` tidak punya kolom `karyawan_id` — satu-satunya identitas yang
bisa dicocokkan lintas direktori — sehingga tidak ada yang bisa diperiksa lebih
awal. Konsekuensinya akun yang belum terdaftar baru ditolak di sisi sys-modern,
dengan pesan yang tetap jelas.

### Langkah 2 — Minta public key, JANGAN private key

Aplikasi anggota hanya perlu **public key** (`SSO_TICKET_PUBLIC_KEY`, format
SPKI). Dengan kunci itu tiket hanya bisa **diperiksa**, tidak bisa dibuat.
Private key hanya ada di auth-sso-api.

Inilah alasan rancangannya asimetris. Alternatif HS256 dengan secret bersama
sama cepatnya, tapi setiap aplikasi yang memegang secret itu bisa **memalsukan**
tiket untuk pengguna mana pun di aplikasi mana pun — satu `.env` bocor
meruntuhkan seluruh SSO.

### Langkah 3 — Konfigurasi

Buat kelas config yang membaca `.env` (`app/Config/Sso.php`), berisi minimal:
saklar aktif, `appCode`, `issuer`, public key, toleransi jam, umur nonce, url
dashboard, base url API, secret SLO, dan kolom pencocokan identitas.

> **Jebakan `.env`:** tulis public key **tanpa tanda kutip**. Parser `.env` CI4
> memproses escape di dalam nilai berkutip, dan itu merusak urutan `\n` pada
> kunci sehingga kunci gagal dibaca OpenSSL.

### Langkah 4 — Verifikator tiket

Tidak perlu paket Composer. `ext-openssl` sudah cukup: `openssl_verify()` +
base64url + `json_decode`. Urutan pemeriksaannya penting:

1. Pecah JWT jadi tiga bagian.
2. Decode header, **patok `alg` ke RS256** — sebelum menyentuh tanda tangan.
3. Verifikasi tanda tangan atas `header.payload`.
4. Baru decode payload dan periksa `exp`/`nbf`/`iat`, `iss`, `aud`, `jti`, `sub`.

Header adalah bagian tiket yang dikendalikan penyerang, jadi ia tidak boleh ikut
menentukan **cara** verifikasi.

### Langkah 5 — Penjaga sekali-pakai

Tiket adalah kredensial pembawa yang lewat di query string: ia gampang
tertinggal di history browser, log proxy, dan header `Referer`. Yang membuat
jejak itu tidak berbahaya adalah jaminan tiket hanya bisa ditukar **sekali**.

hrapi memakai Redis (`SET key val NX EX`). sys-modern tidak punya Redis, jadi
perannya diambil filesystem: `fopen($path, 'xb')` memakai `O_CREAT|O_EXCL` —
satu-satunya cara membuat berkas yang atomik di level sistem operasi, di Windows
maupun Linux. Pola "cek dulu, lalu tulis" **tidak** setara: ada celah balapan di
antara keduanya.

### Langkah 6 — Controller callback

`GET /auth/sso-callback?ticket=…` mengerjakan, berurutan:

verifikasi tiket → bakar `jti` → cocokkan pengguna → `session()->regenerate(true)`
→ tulis sesi → catat log → redirect ke halaman utama.

Dua hal yang wajib:

- **`regenerate(true)` sebelum menulis penanda login.** Naik level privilese dari
  anonim ke terautentikasi harus memakai session ID baru, kalau tidak ID yang
  mungkin sudah ditanam penyerang ikut terautentikasi (session fixation).
- **Tidak ada auto-provisioning.** Tiket membuktikan "orang ini sudah
  diautentikasi SSO", **bukan** "orang ini berhak masuk aplikasi ini". Membuat
  akun otomatis akan memberi setiap pemilik akun SSO sebuah akun di aplikasi
  Anda — itu keputusan admin, bukan keputusan endpoint callback.

### Langkah 7 — Route dan pengecualian filter

Route callback harus dikecualikan dari filter autentikasi **dan** filter ACL:
pengguna belum punya sesi saat menyentuhnya. Yang menjaganya bukan sesi,
melainkan tanda tangan RS256 pada tiket.

> **Route callback wajib `GET`.** Cookie sesi memakai `SameSite=Lax`, yang
> mengirim cookie pada navigasi top-level GET lintas situs — persis bentuk
> redirect dari dashboard SSO — tapi **tidak** pada POST lintas situs. Kalau
> callback diubah jadi POST, cookie sesi yang baru dibuat tidak terkirim balik
> dan pengguna langsung terlempar ke halaman login.

### Langkah 8 — Single Logout

Tiket membawa klaim `sid`, id sesi SSO yang melahirkannya. Simpan `sid` itu di
sesi aplikasi, lalu tanyakan kabarnya secara berkala ke
`POST /auth/session/introspect` (header `x-slo-secret`). Begitu dijawab
`active:false`, akhiri sesi lokal.

Kebijakan kegagalan harus dipikirkan sejak awal:

| Kondisi | Sikap | Alasan |
|---|---|---|
| Dijawab `active:false` | **Akhiri sesi** (fail closed) | Itulah gunanya SLO |
| API tidak bisa dihubungi | **Pertahankan sesi** (fail open) | Server SSO yang mati tidak boleh melempar keluar seluruh pengguna |
| Secret salah (401) | **Pertahankan sesi** | Salah konfigurasi lebih baik ketahuan dari log daripada memadamkan semua orang |

Sebaliknya, logout lokal **tidak** boleh mencabut sesi SSO
(`/auth/session/revoke`) — itu akan melogout pengguna dari HR dan CRM sekaligus.
Mencabut sesi SSO adalah wewenang dashboard SSO, bukan satu aplikasi anggota.

---

## 4. Hal-hal Penting — Kontrol Keamanan dan Akibat bila Diabaikan

| # | Kontrol | Lokasi | Kalau tidak ada |
|---|---|---|---|
| 1 | `alg` dipatok RS256 | `SsoTicket.php:66` | Tiket `alg:none` atau HS256 yang ditandatangani memakai public key ikut diterima — pemalsuan identitas penuh |
| 2 | Verifikasi tanda tangan | `SsoTicket.php:84` | Payload apa pun diterima apa adanya |
| 3 | `aud` = `sys` | `SsoTicket.php:232` | Tiket sah milik HR/CRM bisa diputar ulang di sys-modern |
| 4 | `iss` = `auth-sso` | `SsoTicket.php:212` | Penerbit lain yang kebetulan dipercaya ikut diterima |
| 5 | `exp` wajib ada | `SsoTicket.php:185` | Tiket berlaku selamanya — umur pendek adalah sebagian besar keamanannya |
| 6 | `jti` dibakar atomik | `SsoNonceStore.php:61` | Tiket yang terlihat di history/log bisa dipakai berulang |
| 7 | Panjang kunci ≥ 2048 bit | `SsoTicket.php:124` | Kunci lemah lolos tanpa disadari |
| 8 | `regenerate(true)` | `SsoAuth.php:110` | Session fixation |
| 9 | Tanpa auto-provisioning | `SsoAuth.php:153` | Setiap pemilik akun SSO otomatis punya akun SYS |
| 10 | Nol / >1 baris sama-sama ditolak | `SsoAuth.php:193` | Ambigu sesi siapa yang dibuat; jawaban berbeda juga bisa dipakai memetakan email terdaftar |
| 11 | Pesan gagal seragam | `SsoAuth.php:204` | Respons jadi alat bantu menebak bentuk tiket yang benar |
| 12 | Kode `?sso=` dari daftar tertutup | `Login.php:79` | View merender `$error` tanpa escaping → jalur XSS lewat URL |
| 13 | Nama berkas nonce di-hash | `SsoNonceStore.php:111` | `jti` berisi `../` bisa menulis berkas di luar direktorinya |
| 14 | Public key saja di `.env` | `.env` | Aplikasi anggota bisa **membuat** tiket, bukan hanya memeriksanya |

Nomor 1, 3, 5, 6 dan 8 dikunci oleh test yang benar-benar mencoba menembusnya
(tiket dari kunci lain, tiket HS256, tiket kedaluwarsa, replay) — bukan sekadar
membaca kode.

---

## 5. Penerapan di sys-modern

### Berkas baru

| Berkas | Peran |
|---|---|
| `app/Config/Sso.php` | Seluruh konfigurasi SSO, dibaca dari `.env` berawalan `sso.` |
| `app/Libraries/SsoTicket.php` | Verifikator JWT RS256 (`verify()` di baris 51) |
| `app/Libraries/SsoTicketException.php` | Penolakan tiket; pesannya untuk log, bukan untuk layar |
| `app/Libraries/SsoNonceStore.php` | Penjaga sekali-pakai `jti` (`burn()` di baris 42) |
| `app/Libraries/SsoSlo.php` | Klien Single Logout (`isSessionActive()` di baris 47) |
| `app/Controllers/SsoAuth.php` | `start()` → dashboard SSO; `callback()` → tukar tiket jadi sesi |
| `tests/_support/sso/` | Pasangan kunci khusus test (lihat README di sana) |

### Berkas diubah

| Berkas | Perubahan |
|---|---|
| `app/Config/Routes.php:29` | Route `sso/login` dan `auth/sso-callback` |
| `app/Config/Filters.php:90` | Kedua route dikecualikan dari filter `auth` dan `acl` |
| `app/Filters/AuthFilter.php:48` | Pemeriksaan Single Logout untuk sesi ber-`sso_sid` |
| `app/Libraries/MyAuth.php:51` | `ssoauth` masuk `exceptAuth['class']` — jalur menuju login, bukan objek ACL |
| `app/Controllers/Login.php:79,103` | Pesan `?sso=<kode>`, saklar login lokal, logout sadar-SSO |
| `app/Controllers/Webauthn.php` | Quick Login ikut mati pada mode SSO-only |
| `app/Views/login.php` | Tombol "Masuk dengan SSO" + penyembunyian form lokal |
| `app/Views/partials/footer.php` | Lock screen dimatikan untuk sesi SSO |
| `public/libraries/tas-lib/js/lockscreen.js` | Jawaban `ssoOnly` diantar ke SSO, tidak dihitung percobaan gagal |
| `app/Config/App.php:227` | `app.folder` kosong kini berarti root domain (lihat §7) |

### Konfigurasi `.env`

```
sso.enabled          = true
sso.appCode          = sys                 # harus = key di TICKET_RELAY_APPS
sso.issuer           = auth-sso
sso.ticketPublicKey  = MIIBIjANBgkq...     # PUBLIC key, tanpa tanda kutip
sso.leeway           = 60
sso.nonceTtl         = 300                 # > umur tiket + leeway
sso.dashboardUrl     = https://testsso.transporindo.com/dashboard
sso.apiBaseUrl       = https://testssoapi.transporindo.com
sso.sloSecret        = <SLO_INTROSPECT_SECRET auth-sso-api>
sso.sloPollSeconds   = 60
sso.matchClaim       = email
sso.matchColumn      = email
sso.passwordLoginEnabled = true
```

---

## 6. Keputusan Rancangan dan Alasannya

**Pencocokan lewat `email`, bukan `karyawan_id`.** CRM mencocokkan `karyawan_id`
karena kedua direktorinya mengisi kolom itu dari master karyawan yang sama.
`tbluser` sys-modern tidak punya kolom tersebut, jadi `email` adalah satu-satunya
identitas bersama yang tersedia. **Konsekuensi operasional: email di akun SSO dan
di `tbluser` harus sama persis.** Bila kolom karyawan ditambahkan nanti, cukup
ubah `sso.matchClaim` dan `sso.matchColumn` — kodenya tidak berubah.

**Tanpa dependency Composer.** `firebase/php-jwt` adalah pilihan wajar, tapi
seluruh yang dibutuhkan sudah ada di PHP 8.2. Menghindari paket baru berarti
menghindari satu lagi rantai pasok yang harus dipantau dan di-update.

**Nonce di filesystem, bukan cache.** Cache CI4 tidak menjamin operasi
"tulis-bila-belum-ada" yang atomik. `fopen` mode `x` menjaminnya.

**Login lokal tidak langsung dimatikan.** Selama SSO belum terbukti jalan di
production, mematikan login lokal berarti kalau SSO bermasalah **tidak ada
seorang pun** yang bisa masuk untuk memperbaikinya. Saklar `sso.passwordLoginEnabled`
menyediakan jalannya tanpa memaksa keputusan itu sekarang.

---

## 7. Temuan Sampingan — `app.folder` dan Deployment Root Domain

Ditemukan saat menyiapkan url callback untuk production.

`Config\App::__construct()` menyusun `baseURL` sendiri dari `HTTP_HOST` +
`app.folder`; **`app.baseURL` di `.env` tidak pernah dibaca**. Kode lamanya
memakai `getenv('app.folder') ?: 'sys-modern'`, dan `''` bersifat *falsy* —
sehingga `app.folder` yang sengaja dikosongkan untuk deployment root domain
justru jatuh ke default `sys-modern`:

| Host | `app.folder` | baseURL sebelum | baseURL sesudah |
|---|---|---|---|
| `staging.transporindo.com` | `sys` | `…/sys/` ✔ | `…/sys/` ✔ |
| `sys.transporindo.com` | `''` | `…/sys-modern/` ✘ | `…/` ✔ |
| `sys.transporindo.com` | *tidak diset* | `…/sys-modern/` | `…/sys-modern/` (tidak berubah) |

Akibatnya seluruh url production salah — termasuk url callback SSO — dan alur SSO
putus tepat setelah callback. Diperbaiki dengan membedakan "kosong" dari "tidak
diset", dan dikunci `BaseUrlFolderTest`.

---

## 8. Mode SSO-only dan Lock Screen

### Saklar `sso.passwordLoginEnabled`

Default `true`. Saat `false`, seluruh jalur login lokal ditolak **di server**,
bukan sekadar disembunyikan di view — POST langsung dari curl atau dari tab lama
tidak pernah melihat view itu.

| Jalur | Perilaku saat `false` |
|---|---|
| Form password & Quick Login | disembunyikan |
| `POST login/proses` | redirect `?sso=onlysso` |
| `POST login/unlock` | 403 `{ ssoOnly: true }` |
| `POST forgot-password` | 403 |
| `reset-password`, `reset/<token>` | redirect `?sso=onlysso` |
| `Webauthn::processLogin` (cabang pembuat sesi) | 403 |

### Lock screen dimatikan untuk sesi SSO

Lock screen membuka kuncinya dengan password `tbluser`, sementara pengguna SSO
tidak pernah memakai — dan umumnya tidak tahu — password itu. Bagi dia layar
terkunci bukan pengaman melainkan **jalan buntu**: ia tidak punya kredensial yang
diminta, sampai jatah percobaannya habis dan ia dipaksa logout.

Karena itu sesi yang berasal dari SSO tidak dikunci sama sekali. Cukup dengan
tidak merender overlay-nya: `lockscreen.js` berhenti di baris pertama saat
`#lockscreen-overlay` tidak ada, jadi timer idle, `BroadcastChannel`, dan seluruh
mekanismenya ikut mati. Scriptnya pun tidak dimuat.

**Sesi login lokal tidak terpengaruh** dan tetap terkunci setelah 15 menit
menganggur. Keduanya dikunci test render.

> **Risiko yang perlu disadari:** browser pengguna SSO yang ditinggal terbuka
> tidak lagi terkunci otomatis. Sesinya bertahan sampai kedaluwarsa
> (`Config\Session::$expiration` = 7200 detik / 2 jam) atau sesi SSO-nya dicabut
> lewat Single Logout. Bila proteksi idle tetap diinginkan untuk pengguna SSO,
> jalur yang benar adalah memperpendek umur sesi atau membuat lock screen yang
> membuka kuncinya lewat SSO — bukan mengembalikan lock screen berbasis password.

---

## 9. Verifikasi

```bash
php vendor/bin/phpunit --no-coverage --filter Sso
```

| Berkas test | Jumlah | Yang dijamin |
|---|---|---|
| `SsoTicketTest` | 16 | Tiket sah diterima; tanda tangan diubah, kunci lain, `alg:none`, HS256-palsu, `aud` CRM, `iss` salah, kedaluwarsa, tanpa `exp`/`jti`/`sub` — semua ditolak. Termasuk memastikan public key di `.env` masih terbaca OpenSSL sebagai RSA ≥ 2048 bit |
| `SsoNonceStoreTest` | 6 | Penukaran pertama diterima, replay ditolak; `jti` tidak pernah jadi nama berkas mentah; gc tidak membuka jalan replay |
| `SsoOnlyModeTest` | 13 | Penolakan mode SSO-only lewat request HTTP sungguhan; lock screen mati untuk sesi SSO tapi hidup untuk sesi lokal |
| `SsoWiringTest` | 7 | Route terdaftar & dikecualikan filter; `nonceTtl` > umur tiket; SLO tanpa konfigurasi tidak melogout siapa pun; tombol SSO punya warna di kedua tema |
| `BaseUrlFolderTest` | 5 | Empat bentuk deployment menghasilkan baseURL yang benar |

Test memakai pasangan kunci sendiri dari `tests/_support/sso/`, tidak pernah
menyentuh kunci produksi.

### Uji manual ujung-ke-ujung

1. Pastikan akun SSO punya `SYS` di `access_menu`, dan `tbluser` punya satu baris
   dengan email yang sama.
2. Login di dashboard auth-sso, tekan kartu **SYS** → harus mendarat di `/home`
   sys-modern dalam keadaan sudah login.
3. Tekan back lalu muat ulang url callback yang sama → harus ditolak "Tiket SSO
   sudah pernah dipakai" (bukti anti-replay bekerja).
4. Logout di dashboard auth-sso, lalu buka halaman mana pun di sys-modern →
   dalam `sso.sloPollSeconds` detik sesi harus berakhir sendiri.

Kegagalan tercatat di `writable/logs/` beserta alasan teknisnya; layar hanya
menampilkan pesan umum.

---

## 10. Rollout per Environment

| | Lokal | Staging | Production |
|---|---|---|---|
| Base url | `http://localhost/sys-modern/` | `https://staging.transporindo.com/sys/` | `https://sys.transporindo.com/` |
| `app.folder` | `/sys-modern/` | `sys` | `''` (kosong, **barisnya tetap ada**) |
| `cookie.secure` | `false` | `true` | `true` |
| `sso.dashboardUrl` | `https://testsso.transporindo.com/dashboard` | idem | *belum ditentukan* |
| `sso.apiBaseUrl` | `https://testssoapi.transporindo.com` | idem | *belum ditentukan* |
| `NEXT_PUBLIC_SYS_URL` (di sso) | `…/sys-modern/auth/sso-callback` | `https://staging.transporindo.com/sys/auth/sso-callback` | `https://sys.transporindo.com/auth/sso-callback` |

Urutan penerapan:

1. Deploy kode sys-modern.
2. Setel `.env` server (`app.folder`, `cookie.secure`, seluruh blok `sso.*`).
3. Samakan `sso.sloSecret` dengan `SLO_INTROSPECT_SECRET` di auth-sso-api.
4. **Deploy ulang `sso` dan `ssoapi`** — tanpa ini kartu SYS tidak berfungsi.
5. Pastikan `access_menu` dan email akun uji cocok.
6. Jalankan uji manual §9.
7. Setelah stabil, pertimbangkan `sso.passwordLoginEnabled = false`.

---

## 11. Risiko dan Sisa Pekerjaan

| # | Hal | Dampak | Tindakan |
|---|---|---|---|
| 1 | Belum diuji ujung-ke-ujung secara live | Tidak diketahui apakah ada kejutan di jaringan/proxy | Jalankan §9 di staging |
| 2 | `sso.sloSecret` masih placeholder | Single Logout diam-diam tidak jalan (login SSO tetap jalan) | Samakan dengan auth-sso-api |
| 3 | Host SSO production belum ditentukan | `.env` production belum bisa dilengkapi | Tunggu keputusan infrastruktur |
| 4 | `cookie.secure = false` | Cookie sesi bisa lewat koneksi polos | Setel `true` di staging & production |
| 5 | Rotasi kunci manual | Ganti keypair = update `.env` setiap aplikasi anggota satu per satu | auth-sso-api perlu endpoint JWKS (sudah direncanakan sebagai evolusi ke OIDC) |
| 6 | Kolom `aktif` di `tbluser` tidak diperiksa | Akun nonaktif tetap bisa masuk lewat SSO | Sama seperti login password yang juga tidak memeriksanya — sebaiknya ditegakkan di **kedua** jalur sekaligus, bukan hanya di SSO |
| 7 | Sesi SSO tidak terkunci saat idle | Browser yang ditinggal terbuka tetap login sampai 2 jam | Lihat §8 |
| 8 | Pencocokan bergantung email | Email SSO ≠ email `tbluser` → tidak bisa masuk | Rapikan data, atau tambah kolom karyawan lalu ubah `sso.matchClaim` |

---

## 12. Kesimpulan

Implementasi ini mengikuti rancangan yang sudah dipakai HR dan CRM, tanpa
menambah dependency, tanpa mengubah skema database, dan tanpa mematikan jalur
login yang ada. Kontrol keamanan yang paling menentukan — patokan algoritma,
pemeriksaan `aud`, dan sifat sekali-pakai tiket — bukan hanya diterapkan tapi
juga dikunci oleh test yang mencoba menembusnya.

Yang tersisa sebelum bisa dipakai sungguhan bersifat operasional, bukan
rekayasa: menyamakan satu secret, melengkapi tiga url, dan menjalankan uji manual
di staging.
