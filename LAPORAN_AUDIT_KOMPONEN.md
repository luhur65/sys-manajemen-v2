# Laporan Audit Komponen Front-End — sys-modern

**Target:** `D:\php-project\sys-modern` — CodeIgniter 4.7.4 / PHP 8.2.28
**Tanggal:** 24 Agustus 2026
**Metode:** Inventarisasi statis `public/libraries/` + penelusuran referensi di `app/Views/`, dibandingkan dengan versi rilis terkini dari registry npm (query langsung ke `registry.npmjs.org`)
**Lingkup:** Hanya komponen front-end. Dependency PHP (`composer.lock`) sudah dinilai mutakhir pada Laporan Audit Keamanan 19 Agustus 2026.

---

## 1. Ringkasan Eksekutif

| Kategori | Jumlah |
|---|---|
| Komponen aktif dimuat di setiap halaman | 19 |
| Punya kerentanan diketahui (CVE) | 2 |
| Sudah EOL / tidak dirilis lagi | 4 |
| Bisa diupdate tanpa risiko arsitektural | 6 |
| Temuan konfigurasi (mismatch / dobel-muat) | 3 |
| Folder library mati (tidak direferensikan sama sekali) | 20 |

**Kesimpulan utama:** stack front-end secara fungsional stabil, tetapi tertahan pada **AdminLTE 3.2.0 + Bootstrap 4.6.1** yang keduanya sudah berhenti dirilis. Terdapat 2 komponen dengan CVE aktif (**Moment.js 2.29.1** dan **jQuery UI 1.13.1**) yang dapat ditambal secara independen tanpa menyentuh Bootstrap.

**Rekomendasi strategis: TIDAK melakukan migrasi ke Bootstrap 5 dalam waktu dekat.** Alasannya diuraikan di Bagian 4 — dan alasan itu bukan jqGrid.

---

## 1.1 Status Implementasi (Diperbarui 14 September 2026)

**Tahap 1, 2, dan 3 dari Bagian 5 sudah dikerjakan**, kecuali satu penyimpangan pada jQuery UI. Rincian per komponen ada di tabel Bagian 5; ringkasannya:

| Item | Status |
|---|---|
| Moment.js → 2.30.1 (CVE-2022-24785, CVE-2022-31129) | ✅ Selesai |
| Font Awesome → 6.7.2 | ✅ Selesai |
| jqGrid CSS → `590/` (sinkron dengan JS) | ✅ Selesai (sebelum sesi ini) |
| Select2 3.4.6 duplikat di `user/index.php` dihapus | ✅ Selesai |
| Inputmask → 5.0.10 (satu path, tidak lagi 5.0.6 vs 5.0.7) | ✅ Selesai |
| AutoNumeric → 4.10.10 | ✅ Selesai — lihat catatan versi run-time di bawah |
| Select2 → 4.1.0 | ✅ Selesai |
| jQuery → 3.7.1 (batas maksimal, **bukan** 4.0.0) | ✅ Selesai |
| jQuery UI (JS + kedua tema) → 1.13.3 seragam | ⚠️ **Menyimpang dari rekomendasi** — lihat catatan di bawah |
| Pembersihan folder library mati (~780 MB, Bagian 6.1) | Belum dikerjakan |

**Penyimpangan jQuery UI — perlu keputusan.** JS dan CSS struktur dinaikkan ke **1.14.2**, bukan 1.13.3 seperti rekomendasi Bagian 3.2 (alasan rekomendasi itu: 1.14.2 "menghapus beberapa API yang sudah lama deprecated dan memerlukan pengujian jqGrid — bukan pilihan pertama"). Kedua tema (`cupertino`, `darkhive`) **tidak ikut diperbarui** dan tetap di versi asalnya (1.13.2 dan 1.14.1) karena hanya folder `1.14.2/` yang tersedia secara lokal — tema ThemeRoller yang sepadan belum diunduh. Hasilnya: tiga versi jQuery UI masih hidup berdampingan seperti temuan T-3.6 semula, hanya angkanya bergeser (1.14.2 / 1.13.2 / 1.14.1, sebelumnya 1.13.1 / 1.13.2 / 1.14.1). Smoke test manual di grid `piutangemkl` (drag-and-drop panel Atur Kolom, yang memakai jQuery UI Sortable) berjalan normal, tapi ini bukan pengujian jqGrid yang menyeluruh seperti diminta rekomendasi asli.

**Catatan versi run-time AutoNumeric.** Berkas yang terpasang adalah rilis 4.10.10 resmi (komentar header di `AutoNumeric.js:4` menyatakan `@version 4.10.10`, sesuai npm), tetapi `AutoNumeric.version()` mengembalikan string `"4.10.9"` pada saat dijalankan — string versi internal yang tidak ikut di-bump oleh maintainer upstream di rilis ini. Bukan bug pemasangan; hanya locandaan bila ada kode yang membaca `AutoNumeric.version()` untuk pengecekan.

**Regresi Inputmask yang ditemukan & ditutup (14 September 2026).** Inputmask 5.0.10 menukar konvensi huruf besar/kecil pada alias `"datetime"` dibanding 5.0.6: dulu `mm` = bulan (01-12), sekarang `MM` = bulan dan `mm` = menit (00-59). `public/libraries/tas-lib/js/mains.js` (`initDatepicker()` dan `initMonthpicker()`) masih memakai `inputFormat` lama berhuruf kecil (`"dd-mm-yyyy"`, `"mm-yyyy"`), sehingga field tanggal di seluruh aplikasi (dipakai lewat class `.datepicker`/`.monthpicker` di banyak view, termasuk Laporan Omset EMKL) diam-diam memvalidasi bulan sebagai menit — nilai seperti "14" lolos karena masih di bawah 60. Ditemukan lewat laporan pengguna, bukan lewat smoke test tanggal 14 September di atas (smoke test itu tidak menguji pengetikan nilai tak valid ke field, hanya memastikan pustaka termuat tanpa error konsol). Diperbaiki dengan mengganti ke `"dd-MM-yyyy"` / `"MM-yyyy"`; diverifikasi ulang langsung di Laporan Omset EMKL — "14" pada posisi bulan sekarang ditolak/dibatasi ke 12. Tahun 4 digit (`yyyy`) tidak dibatasi di kedua versi Inputmask — bug terpisah, sudah ada sebelum upgrade ini, belum diperbaiki.

**Verifikasi 14 September 2026:** smoke test langsung di browser (login, dashboard, grid `piutangemkl` dengan data nyata) dengan user berautentikasi. Dikonfirmasi lewat network request (200 OK di seluruh path pustaka yang diubah) dan lewat runtime (`jQuery.fn.jquery`, `jQuery.ui.version`, `moment.version`, dll. dibaca langsung dari console). Tidak ada error konsol yang terkait pustaka manapun; satu-satunya console error adalah peringatan COOP header yang sudah ada sebelumnya (server berjalan di HTTP, bukan menyangkut perubahan ini). `composer test` — 332 test, 1 gagal (`BaseUrlFolderTest::testPengembanganLokal`, murni beda konfigurasi host lokal mesin ini, `App.php` tidak tersentuh perubahan apa pun).

---

## 2. Inventaris Komponen Aktif

Sumber pemuatan: `app/Views/partials/header.php` dan `app/Views/partials/footer.php`.

| # | Komponen | Versi Terpasang | Versi Terbaru (24 Ags 2026) | Lokasi | Status |
|---|---|---|---|---|---|
| 1 | jQuery | 3.6.0 | 3.7.1 (line 3.x) · 4.0.0 (18 Jan 2026) | `adminlte/plugins/jquery/jquery.min.js` | Dapat naik ke 3.7.1 |
| 2 | jQuery UI (JS) | 1.13.1 | 1.13.3 · 1.14.2 (28 Jan 2026) | `jquery-ui/1.13.1/` | **CVE aktif** |
| 3 | jQuery UI (tema cupertino) | 1.13.2 | — | `jquery-ui/cupertino/` | Tidak sinkron |
| 4 | jQuery UI (tema darkhive) | 1.14.1 | — | `jquery-ui/darkhive/` | Tidak sinkron |
| 5 | jQuery UI (plugin AdminLTE) | 1.13.0 | — | `adminlte/plugins/jquery-ui/` | Tidak dimuat |
| 6 | Bootstrap | 4.6.1 | 4.6.2 (line 4, EOL) · 5.3.8 | `adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js` | **EOL Jan 2023** |
| 7 | AdminLTE | 3.2.0 | 3.2.0 (final) · 4.8.5 (20 Ags 2026) | `adminlte/` | **EOL Feb 2022** |
| 8 | Guriddo jqGrid (JS) | **5.9.0** (20 Mar 2026) | mutakhir | `jqgrid/590/js/jquery.jqGrid.min.js` | Mutakhir |
| 9 | Guriddo jqGrid (CSS) | **5.7.0** (09 Mar 2022) | — | `jqgrid/570/css/ui.jqgrid-bootstrap4.css` | **Mismatch dengan JS** |
| 10 | Select2 | 4.0.13 | 4.1.0 (26 Mei 2026) | `adminlte/plugins/select2/` | Dapat naik |
| 11 | Font Awesome Free | 5.15.4 | 6.7.2 · 7.3.1 | `adminlte/plugins/fontawesome-free/` | **EOL** |
| 12 | Moment.js | **2.29.1** | 2.30.1 | `adminlte/plugins/moment/` | **2 CVE aktif** |
| 13 | daterangepicker | 3.1.0 | 3.1.0 (rilis terakhir 2020) | `adminlte/plugins/daterangepicker/` | Sudah mentok |
| 14 | OverlayScrollbars | 1.13.0 | 2.16.0 | `adminlte/plugins/overlayScrollbars/` | **Jangan diupdate** |
| 15 | Inputmask | 5.0.6 (+5.0.7 di AdminLTE) | 5.0.10 (31 Jul 2026) | `inputmask/5.0.6/` | Dapat naik |
| 16 | AutoNumeric | 4.5.4 | 4.10.10 (16 Apr 2026) | `autonumeric/4.5.4/` | Tertinggal jauh |
| 17 | Nestable2 | 1.6.0 | 1.6.0 (2017) | `nestable2/1.6.0/` | Sudah mentok |
| 18 | jquery.highlight | v5 (Johann Burkard) | — | `highlight/highlight.js` | Tidak terawat |
| 19 | Popper.js | 1.16.1 | — | terbundel di `bootstrap.bundle.min.js` | Mengikuti Bootstrap |

### Komponen dimuat kondisional

| Komponen | Versi | Lokasi | Catatan |
|---|---|---|---|
| Select2 (lama) | **3.4.6** (2013) | `app/Views/user/index.php:88-89` | Dimuat di tengah `<div>`, bertabrakan dengan Select2 4.0.13 global |

### Library internal

| Komponen | Ukuran | Catatan |
|---|---|---|
| `tas-lib/js/*` | 25 berkas, **42.515 baris** | Library grid/lookup/pager internal, sepenuhnya berbasis jQuery + jqGrid |

---

## 3. Temuan Prioritas

### 3.1 CRITICAL — Moment.js 2.29.1 memiliki 2 CVE

**Lokasi:** `public/libraries/adminlte/plugins/moment/moment.min.js`, dimuat di `app/Views/partials/footer.php:52`

| CVE | Deskripsi | Diperbaiki di |
|---|---|---|
| CVE-2022-24785 | Path traversal pada pemuatan locale | 2.29.2 |
| CVE-2022-31129 | ReDoS pada parsing string tanggal | 2.29.4 |

**Dampak nyata:** Moment hanya dipakai sebagai dependensi `daterangepicker`. Vektor ReDoS relevan bila ada input tanggal dari user yang diparse Moment.

**Perbaikan:** ganti berkas dengan Moment 2.30.1. API 2.29 → 2.30 kompatibel penuh, tanpa breaking change. Risiko rendah.

---

### 3.2 HIGH — jQuery UI 1.13.1 rentan XSS

**Lokasi:** `public/libraries/jquery-ui/1.13.1/jquery-ui.min.js`, dimuat di `app/Views/partials/header.php:38`

- **CVE-2022-31160** — XSS pada widget `checkboxradio` (isi `.text()` diproses sebagai HTML). Diperbaiki di **1.13.2**; versi terpasang 1.13.1 terdampak.

**Perbaikan:** naik ke **1.13.3** (patch murni, aman) sekaligus menyeragamkan tema. Alternatif 1.14.2 menghapus beberapa API yang sudah lama deprecated dan memerlukan pengujian jqGrid — bukan pilihan pertama.

---

### 3.3 HIGH — Versi CSS dan JS jqGrid tidak sinkron

```
app/Views/partials/header.php:20   jqgrid/570/css/ui.jqgrid-bootstrap4.css   -> v5.7.0 (2022-03-09)
app/Views/partials/footer.php:79   jqgrid/590/js/jquery.jqGrid.min.js        -> v5.9.0 (2026-03-20)
```

Selisih dua versi minor. Struktur DOM yang dihasilkan jqGrid 5.9.0 dapat berbeda dari yang diasumsikan stylesheet 5.7.0 (kedua berkas CSS berbeda 952 byte). Ini sumber laten bug tampilan grid yang sulit dilacak.

**Perbaikan:** arahkan `header.php:20` ke `libraries/jqgrid/590/css/ui.jqgrid-bootstrap4.css`, lalu smoke test visual pada beberapa grid.

---

### 3.4 MEDIUM — Select2 dimuat dua versi pada satu halaman

**Lokasi:** `app/Views/user/index.php:88-89`

```php
<link href="<?= asset('libraries/select2-3.4.6/select2.css') ?>" rel="stylesheet"/>
<script src="<?= asset('libraries/select2-3.4.6/select2.js') ?>"></script>
```

Select2 **3.4.6** (rilis 2013) disuntik di tengah markup `<div>`, padahal `app/Views/partials/footer.php:61` sudah memuat Select2 **4.0.13** secara global. Dua versi mendaftarkan `$.fn.select2` pada objek jQuery yang sama — yang dimuat terakhir menang, dan urutan itu bergantung pada timing parser. API 3.x dan 4.x tidak kompatibel.

**Perbaikan:** migrasikan `#user_roles` ke API Select2 4.x, hapus tag `<link>`/`<script>` inline, lalu hapus folder `select2-3.4.6/`.

---

### 3.5 MEDIUM — Komponen EOL

| Komponen | Rilis terakhir | Konsekuensi |
|---|---|---|
| Bootstrap 4.6.1 | 4.6.2, Jul 2022 | Tidak ada lagi patch keamanan dari upstream |
| AdminLTE 3.2.0 | Feb 2022 | Tidak ada lagi patch; mengunci Bootstrap di v4 |
| Font Awesome 5.15.4 | 5.15.4 | Tidak ada ikon baru; v6/v7 aktif |
| jquery.highlight v5 | ~2008 | Tanpa pemelihara |

Tidak ada CVE aktif yang diketahui pada Bootstrap 4.6.1 maupun AdminLTE 3.2.0 saat laporan ini dibuat, tetapi tidak ada pula jalur perbaikan bila muncul di kemudian hari.

---

### 3.6 LOW — Tiga versi jQuery UI hidup berdampingan

| Berkas | Versi |
|---|---|
| `jquery-ui/1.13.1/jquery-ui.min.js` (JS aktif) | 1.13.1 |
| `jquery-ui/cupertino/jquery-ui.min.css` (tema terang) | 1.13.2 |
| `jquery-ui/darkhive/jquery-ui.min.css` (tema gelap) | 1.14.1 |

Tema gelap (1.14.1) lebih baru dua minor dari JS-nya. jQuery UI 1.14 mengubah sebagian nama kelas CSS internal, sehingga ada style dark-mode yang berpotensi tidak mengikat ke elemen yang dihasilkan JS 1.13.1. Kedua tema diganti-ganti saat runtime di `app/Views/partials/footer.php` (fungsi `applyDarkMode()` / `applyLightMode()`).

**Perbaikan:** samakan seluruhnya ke 1.13.3 (JS + kedua tema).

---

## 4. Analisis Migrasi Bootstrap 5

Bagian ini menjawab pertanyaan spesifik: *"kalau pakai Bootstrap 5, tidak menggunakan jQuery — sedangkan jqGrid membutuhkan jQuery, jadi tidak perlu update Bootstrap."*

### 4.1 Premis yang perlu diluruskan

**a. Bootstrap 5 tidak melarang jQuery.**
Bootstrap 5 menghapus jQuery sebagai *dependency*-nya sendiri — bukan melarang jQuery ada di halaman. Keduanya bisa hidup berdampingan. Bundle Bootstrap 5 bahkan tetap mendaftarkan jQuery plugin bridge secara otomatis bila jQuery terdeteksi (kecuali `<body>` diberi atribut `data-bs-no-jquery`). Artinya **38 pemanggilan imperatif** `$('#crudModal').modal('show')` yang tersebar di views tetap berfungsi di Bootstrap 5 tanpa diubah.

**b. jqGrid 5.9.0 sudah mendukung Bootstrap 5 secara resmi.**
Terbukti dari berkas yang sudah ada di repo ini:

```
public/libraries/jqgrid/590/js/grid.base.js:3592    if(ts.p.styleUI === 'Bootstrap5') {
public/libraries/jqgrid/590/css/ui.jqgrid-bootstrap5.css
```

jqGrid menyediakan `styleUI: 'Bootstrap5'` berdampingan dengan `'Bootstrap4'`. **jqGrid bukan penghalang Bootstrap 5.**

### 4.2 Penghalang yang sebenarnya: AdminLTE 3

AdminLTE 3.2.0 dibangun di atas Bootstrap 4 dan tidak akan pernah mendukung Bootstrap 5. AdminLTE 4.8.5 sudah tersedia (peer dependency `bootstrap ^5.3.8`, tanpa jQuery), tetapi biayanya terukur:

| Yang harus diubah | Jumlah |
|---|---|
| `form-group` (dihapus di BS5) | 131 |
| Utility spacing `ml-`/`mr-`/`pl-`/`pr-` → `ms-`/`me-`/`ps-`/`pe-` | 67 |
| `input-group-append` / `input-group-prepend` (dihapus di BS5) | 35 |
| `data-toggle` / `data-dismiss` / `data-target` → `data-bs-*` | 26 |
| Konfigurasi grid `styleUI: 'Bootstrap4'` → `'Bootstrap5'` | 36 (di 33 berkas) |
| Berkas view yang terdampak | 56 |
| Baris kode `tas-lib` yang perlu diregresi | 42.515 |

Tambahan: AdminLTE 4 mengganti struktur DOM sidebar/navbar, sehingga `adminlte-customized.min.css` dan seluruh logika toggle tema di `app/Views/partials/footer.php` harus ditulis ulang.

### 4.3 Rekomendasi

**Jangan migrasi ke Bootstrap 5 sekarang.** Kesimpulannya sama dengan dugaan awal, tetapi alasannya berbeda: penghambatnya adalah AdminLTE 3 dan volume markup Bootstrap 4 di 56 view — bukan ketergantungan jqGrid pada jQuery.

Strategi yang dianjurkan: **pertahankan Bootstrap 4, tambal komponen secara independen.** Hampir seluruh komponen di Bagian 2 dapat diupdate tanpa menyentuh Bootstrap sama sekali.

Migrasi Bootstrap 5 baru layak dipertimbangkan bila salah satu terjadi:

1. Muncul CVE pada Bootstrap 4 atau AdminLTE 3 yang tidak akan ditambal upstream;
2. Ada rencana rewrite UI menyeluruh yang memang sudah dianggarkan;
3. `tas-lib` sudah dipecah dari monolit 42k baris menjadi modul yang dapat diuji terpisah.

---

## 5. Rencana Update yang Direkomendasikan

### Tahap 1 — Keamanan (risiko rendah, kerjakan lebih dulu)

| Komponen | Dari | Ke | Catatan | Status |
|---|---|---|---|---|
| Moment.js | 2.29.1 | 2.30.1 | Ganti berkas, API kompatibel | ✅ Selesai |
| jQuery UI (JS + kedua tema) | 1.13.1 / 1.13.2 / 1.14.1 | 1.13.3 seragam | Patch keamanan + sinkronisasi | ⚠️ JS+CSS struktur naik ke **1.14.2** (bukan 1.13.3), kedua tema **belum** ikut disamakan — lihat §1.1 |
| Font Awesome | 5.15.4 | 6.7.2 | v6 masih menerima kelas `fas`/`far` lama | ✅ Selesai |

### Tahap 2 — Perbaikan konfigurasi (tanpa update versi)

1. `app/Views/partials/header.php:20` — arahkan CSS jqGrid ke `jqgrid/590/`. ✅ Selesai (sebelum sesi ini)
2. `app/Views/user/index.php:88-89` — migrasikan ke Select2 4.x, hapus Select2 3.4.6. ✅ Selesai
3. Samakan versi Inputmask (repo punya 5.0.6 di `libraries/inputmask/` dan 5.0.7 di `adminlte/plugins/`; hanya 5.0.6 yang dimuat). ✅ Path aktif naik ke 5.0.10; salinan 5.0.7 di `adminlte/plugins/inputmask/` masih ada tapi tetap tidak dimuat (tidak berbahaya, kandidat pembersihan §6.1)

### Tahap 3 — Update independen (perlu pengujian regresi)

| Komponen | Dari | Ke | Area uji | Status |
|---|---|---|---|---|
| AutoNumeric | 4.5.4 | 4.10.10 | Semua input numerik / format rupiah | ✅ Selesai |
| Inputmask | 5.0.6 | 5.0.10 | Input tanggal & mask khusus | ✅ Selesai |
| Select2 | 4.0.13 | 4.1.0 | Seluruh dropdown pencarian | ✅ Selesai — diuji langsung di grid `piutangemkl` |
| jQuery | 3.6.0 | 3.7.1 | Smoke test menyeluruh | ✅ Selesai — diuji langsung (login, dashboard, grid) |

### Yang TIDAK boleh diupdate

| Komponen | Alasan |
|---|---|
| **OverlayScrollbars 1.13.0 → 2.x** | v2 menghapus wrapper plugin jQuery, sedangkan `adminlte/dist/js/adminlte.js:666` dan `:1783` memanggil `$(...).overlayScrollbars({...})`. Sidebar dan control-sidebar akan rusak. |
| **jQuery 3.6.0 → 4.0.0+** | **BATAS MAKSIMAL UPDATE: v3.7.1.**<br>Dilarang naik ke jQuery v4.0.0+ karena dua alasan utama:<br>1. **Runtime Error dari Bootstrap 4:** Memicu `util.js:187 Uncaught Error: Bootstrap's JavaScript requires at least jQuery v1.9.1 but less than v4.0.0`. Berkas internal Bootstrap 4 (`bootstrap.bundle.min.js`) memiliki *hardcoded check* `version[0] >= 4` dan melempar fatal error saat diinisialisasi.<br>2. **Depregasi API:** jQuery 4 menghapus `$.isArray` & `$.isFunction` (dipakai 11x oleh Select2 4.0.13).<br>*(Catatan: jqGrid 5.9.0 sendiri aman dari isu API ini karena memakai `jgrid.isFunction` internal, tetapi tetap tidak bisa berjalan akibat pengecekan versi Bootstrap 4 di atas)*. |
| **Bootstrap 4 → 5** | Lihat Bagian 4. |

---

## 6. Temuan Tambahan

### 6.1 Folder library mati — 780 MB

`public/libraries/` berukuran **780 MB**. Folder-folder berikut **tidak memiliki satu pun referensi** di `app/`, `public/libraries/tas-lib/`, maupun `public/js/`:

| Folder | Ukuran |
|---|---|
| `stimulsoft-report` | **520 MB** |
| `pdfjs2` | 15 MB |
| `pdfjs-2.9.359` | 6 MB |
| `easyui` | 6 MB |
| `font` | 5 MB |
| `jquery-ui-1.11.4` | 2 MB |
| `tas-lookup` | 2 MB |
| `select2` (duplikat dari AdminLTE) | 2 MB |
| `jquery/{1.7.1, 1.9.0, 1.11.0}` | — |
| `jqgrid/{460, 511, 530, 550, 560}` | — |
| `datatable`, `crypto-js`, `socket.io-client`, `graph`, `gkcalendar`, `FCBKcomplete-master`, `jQuery-jalan`, `ajaxfileupload`, `jquery-simple-color-master`, `jquery-ui-multiselect`, `tas-grid-column`, `toastify-js-master`, `bootstrap`, `list`, `icon`, `img`, `css` | sisanya |

Pembersihan memangkas repo dari ~780 MB menjadi di bawah 100 MB.

**Prasyarat sebelum menghapus:** verifikasi tidak ada pemuatan lewat path dinamis atau dari berkas di luar `app/`, dan lakukan penghapusan dalam commit terpisah agar mudah di-revert.

Catatan: folder `bootstrap/` di akar `libraries/` berisi source Bootstrap **3** yang tidak dimuat sama sekali — Bootstrap yang aktif berasal dari `adminlte/plugins/bootstrap/`.

### 6.2 Catatan lisensi jqGrid (non-teknis)

`public/libraries/jqgrid/590/package.json` dan `bower.json` mencantumkan:

```json
"licenses": [{ "type": "Creative Commons Attribution-Non-Commercial 3.0 License" }]
```

Berkas yang terpasang adalah build **CC-BY-NC-3.0 (non-komersial)**, sementara sys-modern adalah sistem operasional perusahaan. Guriddo menjual lisensi komersial terpisah untuk penggunaan seperti ini.

Ini bukan temuan teknis dan tidak dapat diselesaikan dari sisi kode — perlu dikonfirmasi ke manajemen apakah lisensi komersial sudah dimiliki.

### 6.3 Berkas uji di direktori publik

Di luar lingkup audit komponen, tercatat berkas berikut berada di `public/` sehingga dapat diakses langsung: `test_acos.php`, `test_acos_db.php`, `test_db.php`, `test_diff.php`, `test_getargs.php`, `test_param.php`, `test_qs.php`, `test_save.php`, `test_webauthn.php`. Perlu ditinjau pada audit keamanan terpisah.

---

## 7. Referensi Versi

Seluruh versi "terbaru" diperoleh dengan query langsung ke `registry.npmjs.org` pada 24 Agustus 2026:

| Paket npm | dist-tag `latest` | Tanggal rilis |
|---|---|---|
| `jquery` | 4.0.0 (line 3.x: 3.7.1) | 18 Jan 2026 (3.7.1: 28 Ags 2023) |
| `jquery-ui` | 1.14.2 | 28 Jan 2026 |
| `jquery-ui-dist` | 1.13.3 | 26 Mei 2024 |
| `bootstrap` | 5.3.8 (`latest-4`: 4.6.2) | 26 Ags 2025 (4.6.2: 19 Jul 2022) |
| `admin-lte` | 4.8.5 (line 3.x: 3.2.0) | 20 Ags 2026 (3.2.0: 07 Feb 2022) |
| `select2` | 4.1.0 | 26 Mei 2026 |
| `moment` | 2.30.1 | 27 Des 2023 |
| `inputmask` | 5.0.10 | 31 Jul 2026 |
| `autonumeric` | 4.10.10 | 16 Apr 2026 |
| `overlayscrollbars` | 2.16.0 | 09 Mei 2026 |
| `nestable2` | 1.6.0 | 22 Okt 2017 |
| `daterangepicker` | 3.1.0 | 24 Mei 2020 |
| `@fortawesome/fontawesome-free` | 7.3.1 (line 6.x: 6.7.2) | 15 Jul 2026 (6.7.2: 16 Des 2024) |

Guriddo jqGrid tidak dipublikasikan di npm (produk komersial). Versi terpasang 5.9.0 bertanggal 20 Maret 2026 — build terkini sejauh yang dapat diverifikasi dari berkas di repo.
