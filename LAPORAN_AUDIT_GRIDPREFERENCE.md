# Laporan Audit Fitur GridPreference — sys-modern

**Target:** `D:\php-project\sys-modern` — CodeIgniter 4.7.4 / PHP 8.2.28
**Tanggal:** 26 Agustus 2026
**Metode:** Telaah statis rantai lengkap fitur (controller, 3 file JS, Routes, Filters, MyAuth), inventarisasi 33 view ber-jqGrid, pembacaan langsung file preferensi yang benar-benar tersimpan di `writable/grid_preferences/`, dan pembacaan implementasi `remapColumns` pada Guriddo jqGrid 5.9.0
**Pertanyaan yang dijawab:** apakah GridPreference — yang saat ini hanya aktif di `piutangemkl` — sudah bisa diterapkan ke seluruh grid modul

---

## 1. Ringkasan Eksekutif

**Belum aman dirilis ke semua grid pada kondisi awal.** Secara mekanis pengaktifannya memang hanya satu baris (`gridPreference: true`), dan 32 dari 33 view grid sudah memenuhi syarat toolbar. Tetapi ditemukan **4 bloker** yang akan langsung merusak grid begitu fitur dinyalakan lintas modul, ditambah 5 jalur kehilangan data, 6 temuan tingkat sedang, dan 4 temuan keamanan.

| Kategori | Ditemukan | Selesai | Sebagian | Terbuka |
|---|---|---|---|---|
| Bloker | 4 | 4 | — | 0 |
| Kehilangan data / inkonsistensi | 5 | 2 | 1 | 2 |
| Sedang | 6 | 3 | — | 3 |
| Keamanan | 4 | 3 | — | 1 |

Perbaikan dikerjakan dalam tiga gelombang.

**Poin 1–3** — kunci preferensi dipindah ke sisi server, rekonsiliasi preferensi terhadap `colModel`, dan penyatuan dua jalur simpan menjadi satu serializer berversi. Ketiganya menutup seluruh bloker.

**Peninjauan skala.** Fitur ditinjau ulang khusus untuk 33 grid, bukan satu grid. Peninjauan itu menemukan lapisan penyimpanannya belum layak: baca-ubah-tulis pada berkas preferensi kini berada di bawah satu kunci eksklusif. Uji tabrakan nyata dengan 10 proses paralel menunjukkan pola lama menghapus **20 dari 20** grid yang sudah tersimpan; pola baru mempertahankan seluruhnya. Rinciannya di Bagian 5.

**Poin 4 & 6** — idempotensi inisialisasi, bilah cadangan untuk grid tanpa toolbar, dan validasi payload di sisi server. Rinciannya di Bagian 6.

**Status rollout:** keempat tahap sudah bisa dijalankan; seluruh 33 grid dapat dinyalakan. Yang masih terbuka adalah konvergensi lintas perangkat — lihat Bagian 8.

---

## 2. Kondisi Awal

| Item | Angka |
|---|---|
| View yang menginisialisasi jqGrid | 33 |
| Sudah memakai `gridPreference: true` | 1 (`piutangemkl`) |
| Punya `toolbar:` — saat itu syarat mutlak tombol panel | 32; kecuali `app/Views/useracl/form.php`. Sejak Bagian 6.2 tidak lagi jadi syarat |
| Memakai `colNames` klasik | 1 — `app/Views/truckingtradoluardetailexp/index.php:129` |
| Halaman dengan dua grid | 3 — `menu`, `roles`, `truckingtradoluar` |
| Memakai `rownumbers` (melahirkan kolom `rn`) | 32 |
| Memakai `sortable: true` (drag header bawaan jqGrid) | seluruhnya |
| File preferensi tersimpan | 2 file, 4 kunci |

Isi nyata `writable/grid_preferences/` pada saat audit:

| Kunci | Jumlah kolom | Menyimpan `rn`? |
|---|---|---|
| `_sys_modern_piutangemkl_index_jqGrid` | 16 | tidak |
| `_sys_modern_User_index_jqGridAcl` | 3 | tidak |
| `_sys_modern_Menu_index_jqGrid` | 10 | **ya** |
| `_sys_modern_Omsetmarketingmdn_index_jqGrid` | 11 | **ya** |

Ketidakseragaman kolom `rn` di tabel itu bukan kebetulan — itu jejak langsung dari temuan **T-8**.

---

## 3. Temuan

Nomor baris merujuk kondisi **sebelum** perbaikan.

### 3.1 Bloker

#### T-1 · Preferensi basi merusak `colModel` — `ColumnSettingsManager.js:146`

`_applyOrder()` menyusun array permutasi dengan `cm.findIndex(c => c.name === targetName)`. Bila nama kolom yang tersimpan sudah tidak ada di `colModel` — developer mengganti nama atau menghapus kolom — `findIndex` mengembalikan `-1`, dan `-1` itu ikut masuk ke permutasi.

Implementasi `remapColumns` di jqGrid 5.9.0 menugaskannya tanpa validasi apa pun:

```js
function r(t){ var r = t.length ? jt.makeArray(t) : jt.extend({},t);
               jt.each(i, function(e){ t[e] = r[this]; }); }
r(o.p.colModel), r(o.p.colNames), r(o.grid.headers)
```

`r[-1]` bernilai `undefined`, jadi `colModel` terisi `undefined` dan grid pecah. `_applyVisibility()` juga memanggil `hideCol` pada kolom yang tidak ada.

**Dampak:** satu kali rename kolom di modul mana pun cukup untuk merusak grid semua user yang sudah menyimpan preferensi di modul itu. Tidak ada validasi preferensi terhadap `colModel` di titik mana pun.

#### T-2 · Kolom baru tidak pernah muncul di panel — `ColumnSettingsManager.js:74`

`_loadViaGPM()` membangun panel murni dari `localStorage`, dan `_syncPanelToGrid()` (baris 525) hanya menyusun ulang item yang sudah ada — tidak pernah membuat item untuk kolom yang belum punya entri.

**Dampak:** kolom yang baru ditambahkan developer tidak bisa dilihat maupun di-toggle user selamanya, sampai ia menekan Reset. Diperparah `GridPreferenceManager.js:174` yang memberi kolom tanpa preferensi `order: 9999`, sehingga kolom baru selalu terlempar ke ujung kanan.

#### T-3 · Grid ber-`colNames` menampilkan judul tertukar — `GridAutoInjector.js:26`

Injector menyusun ulang `gridOptions.colModel` saja. Saat inisialisasi, jqGrid memasangkan `colNames[i]` ke `colModel[i]` per indeks, jadi judul kolom akan salah tempel.

Menariknya `remapColumns` **sudah** ikut memetakan `p.colNames`, jadi drag di panel aman; yang bocor khusus jalur inisialisasi. Terdampak: `truckingtradoluardetailexp`.

#### T-4 · Kunci preferensi diturunkan dari `window.location.pathname` — `GridAutoInjector.js:14`

Dua masalah, keduanya terkonfirmasi pada data nyata:

- **Routes.php mendaftarkan empat varian per modul.** `Routes.php:420-423` mendaftarkan `Piutangemkl`, `piutangemkl`, `Piutangemkl/index`, dan `piutangemkl/index` untuk controller yang sama. Empat kunci untuk satu grid.
- **Base path ikut terbawa.** Kunci yang tersimpan berbunyi `_sys_modern_piutangemkl_index_jqGrid`. Begitu aplikasi dipindah docroot, seluruh preferensi user menjadi yatim.

Halaman berparameter seperti `/cabang/form/MDN/edit` juga melahirkan satu kunci per record.

### 3.2 Kehilangan data / inkonsistensi

#### T-5 · Preferensi server ditimpa diam-diam di perangkat baru — `GridAutoInjector.js:37-42`

`GridPreferenceManager.load()` (asinkron) dan `ColumnSettingsManager.init()` (sinkron) dipanggil pada tick yang sama. Di browser baru urutannya menjadi:

1. `loadSync` mengembalikan `null` → grid dirender dengan tata letak bawaan
2. `init` membaca `localStorage` yang masih kosong → panel dibangun dari tata letak **bawaan**
3. AJAX selesai → preferensi asli masuk `localStorage`, tetapi **grid tidak pernah dirender ulang**
4. User menggeser satu kolom atau menekan Terapkan → tata letak bawaan menimpa preferensi asli di server

**Status: belum diperbaiki** (poin 5 pada rencana). Namun migrasi kunci di sisi klien — lihat Bagian 4.1 — membuat pemutakhiran ini tidak melewati jalur tersebut, sehingga tidak ada preferensi yang hangus saat rilis.

#### T-6 · Sinkronisasi server praktis satu arah — `GridPreferenceManager.js:223`

`if (local) return local;` — server hanya dibaca ketika `localStorage` kosong, dan tidak pernah divalidasi ulang. Perubahan di perangkat A tidak akan pernah sampai ke perangkat B. Tidak ada penengah berupa `updatedAt` atau versi.

**Status: sebagian.** Amplop v2 kini membawa `updatedAt`, jadi fondasi penengahnya sudah ada; aturan konvergensinya belum dipasang.

#### T-7 · Race pada file JSON per-user — `GridPreference.php:54`

`file_put_contents` tanpa `LOCK_EX`, dan pola *read-modify-write* menulis ulang **seluruh** file berisi semua grid milik user.

- Dua tab, atau dua grid pada satu halaman (ada 3 halaman seperti itu), menyimpan bersamaan → preferensi salah satu hilang.
- Tulisan terpotong → `GridPreference.php:43` `json_decode(...) ?: []` → **seluruh preferensi user terhapus diam-diam** pada penyimpanan berikutnya.

**Status: diperbaiki.** Awalnya ditunda ke poin 5, tetapi peninjauan skala menunjukkan ia berubah dari teoretis menjadi rutin begitu 33 grid aktif. Buktinya di Bagian 5.2, perbaikannya di Bagian 5.3.

#### T-8 · Dua jalur simpan menghasilkan dua format

| Jalur | Kolom sistem | Perhitungan `order` |
|---|---|---|
| `resizeStop` → `extractFromGrid` (`GridPreferenceManager.js:116`) | ikut disimpan | dari `colModel` penuh |
| Panel Terapkan → `_saveViaGPM` (`ColumnSettingsManager.js:59`) | dibuang | dihitung ulang dari 0 |

Terkonfirmasi pada data nyata: dua kunci menyimpan `rn`, dua lainnya tidak (lihat tabel Bagian 2). Entri lama juga belum punya field `frozen`.

#### T-9 · Drag header bawaan tidak tersimpan — lalu tiba-tiba tersimpan

Seluruh view memakai `sortable: true`, jadi user bisa menyusun ulang kolom dengan menggeser header. Peristiwa itu tidak di-hook sama sekali; yang di-hook hanya `resizeStop`. Akibatnya geser → refresh → urutan hilang. Tetapi bila user kebetulan mengubah lebar kolom setelah menggeser, serializer ikut menyimpan urutan hasil geseran. Dari sisi user perilakunya tidak bisa ditebak.

**Status: belum diperbaiki.**

### 3.3 Sedang

| Kode | Temuan | Lokasi | Status |
|---|---|---|---|
| T-10 | `_injectButton` menambah listener `document` (capture), `scroll`, dan `resize` setiap kali dipanggil, dan menempel tombol tanpa cek duplikat. Tiga halaman dua-grid langsung menggandakannya | `ColumnSettingsManager.js:463` | **diperbaiki** (Bagian 6.1) |
| T-11 | Menghapus class `d-flex` dari toolbar milik aplikasi. Saat ini inert karena `#t_jqGrid` di sys-modern memang kosong — `loadGlobalSearch` di `mains.js:1428` ternyata **kode mati**, tidak pernah dipanggil — tetapi menjadi ranjau begitu ada modul yang mengisi toolbarnya | `ColumnSettingsManager.js:469` | **diperbaiki** (Bagian 6.1) |
| T-12 | `useracl/form.php` tidak punya `toolbar:`, tombol tidak bisa disuntikkan; hanya `console.warn`, tanpa fallback | `app/Views/useracl/form.php` | **diperbaiki** (Bagian 6.2) |
| T-13 | `setTimeout(300)` — angka arbitrer, tanpa retry dan tanpa pemeriksaan ulang di `loadComplete` | `GridAutoInjector.js:34` | belum |
| T-14 | Lebar desktop bocor ke mobile. View menghitung `isDesktop ? md_dekstop_1 : md_mobile_1`, tetapi preferensi tidak punya dimensi perangkat. Hanya menggigit lewat jalur sinkronisasi server, yang justru persis kasus login pertama di ponsel | seluruh view grid | belum |
| T-15 | Bila `sysmodern_lockscreen_userid` tidak ada, kunci jatuh ke `'guest'` dan seluruh user di browser itu berbagi satu preferensi | `GridPreferenceManager.js:40` | belum |

### 3.4 Keamanan

| Kode | Temuan | Tingkat | Status |
|---|---|---|---|
| S-1 | `delete()` adalah GET yang mengubah state. Filter `csrf` global (`Filters.php:84`) hanya memeriksa POST/PUT/PATCH/DELETE, jadi `<img src=".../gridpreference/delete?grid_name=...">` di halaman mana pun menghapus preferensi user yang sedang login | Rendah | **diperbaiki** |
| S-2 | Tidak ada validasi maupun batas ukuran pada `grid_name` dan `preferences`. User terautentikasi bisa mengirim payload sebesar apa pun dan kunci sebanyak apa pun, ditulis mentah ke disk | Sedang | **diperbaiki** (Bagian 6.3) |
| S-3 | Stored self-XSS: `col.name` diinterpolasi tanpa escape ke atribut HTML dan `onclick` inline (`ColumnSettingsManager.js:215` dan `:226`); sumbernya file preferensi yang diterima server tanpa validasi. Scope-nya akun sendiri, jadi tingkatnya rendah — tetapi menjadi celah hardening nyata begitu panel ini dirender di semua modul | Rendah | **diperbaiki** (Bagian 6.4) |
| S-4 | `mkdir($dir, 0777, true)` terlalu permisif; `getUserId()` memakai `header()`/`echo`/`exit` mentah sehingga melewati response CI4 dan filter `secureheaders` | Rendah | belum |
| — | *(bukan temuan, karena itu tidak ikut dihitung)* `gridpreference` di `MyAuth.php:53` hanya dikecualikan dari ACL, sementara filter `auth` global dan `getUserId()` tetap menjaga sesi. CSRF pada `save()` juga aman: `regenerate = false`, dan `$.ajaxSetup` di `header.php:71` memasang `X-CSRF-TOKEN` ke semua AJAX | — | sudah benar |

---

## 4. Perbaikan yang Diterapkan

### 4.1 Poin 1 — Kunci preferensi dibentuk di sisi server

**Menutup T-4.**

Kunci tidak lagi diturunkan dari `window.location.pathname`. `app/Views/partials/header.php:102-119` menghitung scope dari router dan mengirimkannya ke browser:

```php
$gridPrefCtrl  = $gridPrefRouter->controllerName();   // \App\Controllers\Piutangemkl
$gridPrefMtd   = $gridPrefRouter->methodName();       // index
$gridPrefScope = strtolower($gridPrefCtrl) . '.' . strtolower($gridPrefMtd);
```

```js
window.gridPrefScope = 'piutangemkl.index';
```

`GridAutoInjector.js:47` menyusunnya menjadi `piutangemkl.index.jqGrid`. Modul boleh memaksa kunci sendiri lewat opsi `gridPrefKey`, dan bila scope dari server tidak tersedia — misalnya route berbasis closure — `fallbackScope()` (`:28`) tetap menormalkan pathname: base path dibuang, huruf dikecilkan, dan hanya dua segmen pertama dipakai sehingga `/cabang/form/MDN/edit` tidak lagi melahirkan satu kunci per record.

**Migrasi.** Kunci lama dipetakan ke kunci baru dengan satu aturan yang diterapkan di kedua sisi:

> Kunci lama cocok dengan kunci baru bila ia berakhiran `_<gridId>` **dan** sisa segmennya, dalam huruf kecil, berakhiran `[controller, method]` — atau berakhiran `[controller]` saja ketika methodnya `index`, karena `/piutangemkl` dan `/piutangemkl/index` adalah halaman yang sama.

- **Klien** — `GridPreferenceManager.js:175` `migrateLegacyLocal()` memindahkan entri `localStorage` saat pembacaan pertama. Ini yang membuat pemutakhiran tidak melewati jalur T-5.
- **Server** — `GridPreference.php:156` `migrateLegacyKeys()` memindahkan kunci di file JSON saat `load()`, dan `save()` membersihkan sisa kunci lama supaya satu grid tidak menyimpan dua salinan.

Dry-run terhadap 4 kunci yang benar-benar tersimpan: seluruhnya terpetakan.

| Kunci lama | Kunci baru | Kolom |
|---|---|---|
| `_sys_modern_User_index_jqGridAcl` | `user.index.jqGridAcl` | 3 |
| `_sys_modern_piutangemkl_index_jqGrid` | `piutangemkl.index.jqGrid` | 16 |
| `_sys_modern_Menu_index_jqGrid` | `menu.index.jqGrid` | 10 |
| `_sys_modern_Omsetmarketingmdn_index_jqGrid` | `omsetmarketingmdn.index.jqGrid` | 11 |

### 4.2 Poin 2 — Rekonsiliasi preferensi terhadap `colModel`

**Menutup T-1 dan T-2.**

`GridPreferenceManager.js:290` `reconcile(baseColModel, savedPrefs)` menjadi gerbang tunggal: preferensi tersimpan tidak pernah dipercaya apa adanya.

- Nama kolom yang sudah tidak ada di `colModel` **dibuang**, sehingga `remapColumns` mustahil menerima indeks `-1`.
- Kolom yang ada di `colModel` tetapi belum punya preferensi **disisipkan tepat setelah tetangga kirinya** di `colModel` asli — bukan dilempar ke `order: 9999`. Kolom yang dipasang developer di paling kiri pun tetap muncul paling kiri.
- Hasilnya selalu daftar lengkap dengan `order` 0..n-1.

Fungsi ini dipakai `applyToColModel()` (jalur render grid) maupun `_loadViaGPM()` (jalur pembangunan panel), jadi grid dan panel dijamin melihat daftar kolom yang sama.

Lapisan pertahanan kedua dipasang di `ColumnSettingsManager.js`: `_applyOrder()` (`:133`) menyaring nama yang tidak ada di `colModel` hidup, lalu **memeriksa ulang** permutasi sebelum memakainya (`:169`) — panjang harus sama, tiap indeks tepat sekali, tidak ada nilai negatif. `_applyVisibility()` dan `_applyFreeze()` juga menolak kolom yang tidak dikenal lewat `_liveNameSet()` (`:90`).

**T-3 ikut tertutup** sebagai turunannya. `GridAutoInjector.js:77` mendeteksi grid ber-`colNames` dan meneruskan `{ preserveOrder: true }`, sehingga lebar, sembunyi, dan freeze tetap diterapkan sementara urutan `colModel` dibiarkan utuh. Panelnya menonaktifkan drag dan meredupkan gagang gesernya, jadi user tidak ditawari sesuatu yang tidak akan tersimpan.

### 4.3 Poin 3 — Satu serializer, satu amplop berversi

**Menutup T-8.**

`GridPreferenceManager.js:252` `serialize(gridSelector)` menjadi satu-satunya pembentuk preferensi. Ia membaca `colModel` yang sedang hidup, membuang kolom sistem jqGrid (`SYSTEM_COLS = ['rn','cb','subgrid']`, `:38`), dan menghitung ulang `order` dari 0.

Kedua jalur simpan kini bermuara ke sana:

- `resizeStop` → `saveFromGrid()` (`:266`)
- Panel Terapkan → `_saveViaGPM()` (`ColumnSettingsManager.js:50`) → `saveFromGrid()`

Urutan di `_apply()` yang membuat ini bekerja: langkah 1–3 sudah memindahkan seluruh perubahan panel ke `colModel`, jadi serializer tinggal membaca grid — sumber kebenaran yang sama persis dengan jalur autosave. Panel tidak lagi membangun bentuknya sendiri dari DOM.

Yang ditulis sekarang selalu amplop berversi:

```json
{ "v": 2, "updatedAt": 1756207800000,
  "columns": [ { "name": "FTgl", "width": 100, "hidden": false, "frozen": false, "order": 0 } ] }
```

Pembaca di klien (`unwrap`, `:77`) dan di server (`columnsOf`, `GridPreference.php:139`) tetap menerima array telanjang format v1, sehingga data lama terbaca tanpa migrasi manual.

### 4.4 Ikutan — endpoint `delete` menjadi POST

**Menutup S-1.** Sebagai konsekuensi penyatuan jalur, `deleteServer()` kini mengirim POST dengan badan JSON. `Routes.php:43` diubah menjadi `$routes->post(...)` dan `GridPreference::delete()` (`:256`) membaca `grid_name` dari badan permintaan. Filter `csrf` CI4 sekarang benar-benar menjaganya, dan penghapusan lewat `<img src="...">` tidak lagi mungkin.

### 4.5 Berkas yang tersentuh

| Berkas | Sifat perubahan |
|---|---|
| `public/libraries/tas-lib/js/GridPreferenceManager.js` | ditulis ulang — serializer, `reconcile`, amplop v2, migrasi kunci |
| `public/libraries/tas-lib/js/GridAutoInjector.js` | ditulis ulang — kunci dari scope server, penjaga `colNames` |
| `public/libraries/tas-lib/js/ColumnSettingsManager.js` | `_saveViaGPM`, `_loadViaGPM`, `_applyOrder`, `_applyVisibility`, `_applyFreeze`, `init` |
| `app/Controllers/GridPreference.php` | migrasi kunci lama, `delete` menjadi POST, penyimpanan terkunci, dokumentasi format |
| `app/Config/Routes.php` | `gridpreference/delete` menjadi POST |
| `app/Views/partials/header.php` | memancarkan `window.gridPrefScope` |
| `tests/unit/GridPreferenceKeyTest.php` | baru |
| `tests/unit/GridPrefScopeTest.php` | baru |
| `tests/unit/GridPreferenceStorageTest.php` | baru |
| `tests/unit/GridPreferenceValidationTest.php` | baru |

Daftar ini mencakup ketiga gelombang perbaikan; rincian per gelombang ada di Bagian 4, 5.3, dan 6.

---

## 5. Kesiapan pada Skala Penuh

Poin 1–3 dirancang per-grid, jadi sebagian besar memang tidak berubah sifatnya ketika jumlah grid naik: kunci sudah unik per `controller.method.gridId`, rekonsiliasi bekerja terhadap `colModel` masing-masing grid, dan serializer tidak menyimpan apa pun yang bergantung pada grid lain. Yang **tidak** per-grid adalah lapisan penyimpanannya — dan di situlah letak masalahnya.

### 5.1 Yang diperiksa dan ternyata aman

| Pertanyaan skala | Hasil |
|---|---|
| Ada nama kolom bisnis yang bentrok dengan `LOCKED_COLS` (`id`, `rn`, `cb`, `subgrid`)? | Tidak ada, dari 332 kolom di 33 grid |
| Ada view grid yang dirender lebih dari satu controller? Itu akan memecah kunci untuk grid yang secara visual sama | Tidak ada; setiap view grid punya tepat satu controller |
| Halaman dua-grid tertukar kuncinya? | Tidak; `gridId` membedakan (`jqGrid` vs `jqGridAcl`), dan ada tesnya |
| Halaman berparameter melahirkan kunci per record? | Tidak lagi; `cabang/form/MDN/edit` dan `.../SBY/edit` menghasilkan kunci yang sama |
| Beban `localStorage`? | ~29 KB tersebar di 33 kunci terpisah, jauh di bawah kuota 5 MB |

### 5.2 Yang ternyata tidak layak: penyimpanan berkas

Satu berkas menampung **seluruh** grid milik satu user, dan setiap penyimpanan menulis ulang keseluruhannya. Dengan 33 grid dan 332 kolom:

| Ukuran | Nilai |
|---|---|
| Berkas per user, `JSON_PRETTY_PRINT` (kondisi awal) | 68,7 KB |
| Berkas per user, compact (sekarang) | 29,4 KB — turun 57% |
| Ditulis untuk mengubah **satu** grid | seluruh berkas — amplifikasi ~31x |
| Biaya satu penyimpanan (baca + decode + encode + tulis) | ~15 ms |

Amplifikasi 31x itu sendiri masih bisa ditolerir; penyimpanan hanya terjadi pada resize ter-debounce dan tombol Terapkan, bukan jalur panas. Yang tidak bisa ditolerir adalah tabrakan tulisnya.

**Uji tabrakan nyata.** Sepuluh proses PHP paralel, masing-masing 30 penyimpanan ke satu berkas yang sudah berisi 20 grid, dengan muatan seukuran grid sungguhan:

| Pola | JSON tetap valid | Grid awal utuh | Penyimpanan yang bertahan |
|---|---|---|---|
| `file_get_contents` + `file_put_contents` (kondisi awal) | ya | **0 dari 20** | 6 dari 10 |
| Baca-ubah-tulis di bawah `LOCK_EX` (sekarang) | ya | **20 dari 20** | 10 dari 10 |

Kolom "grid awal utuh" itu yang paling penting dan paling tidak terduga. Kehilangan seluruh 20 grid bukan karena tulisan yang bertabrakan, melainkan karena satu proses **membaca** berkas saat proses lain sedang menulisnya: `json_decode` gagal, `?: []` menerjemahkannya sebagai "user belum punya preferensi", dan penyimpanan berikutnya menuliskan keadaan kosong itu. Satu berkas terpotong menghapus seluruh tata letak milik user di semua modul.

Pada satu grid, peluang dua penulisan berbarengan mendekati nol. Pada 33 grid — dengan tiga halaman yang punya dua grid sekaligus, dan pemakaian banyak tab yang lumrah — itu menjadi kejadian rutin.

### 5.3 Perbaikan yang menyusul

`GridPreference::mutateData()` membungkus baca-ubah-tulis dalam **satu** `LOCK_EX` pada handle yang sama, jadi tidak ada lagi celah antara membaca dan menulis. `loadData()` membaca di bawah `LOCK_SH`. Ketiga endpoint (`load`, `save`, `delete`) melewatinya.

Dua perubahan pendamping:

- **Berkas rusak dikarantina, bukan dianggap kosong.** Isinya disalin ke `<berkas>.corrupt-<timestamp>` dan dicatat ke log. Jalur "hapus semuanya diam-diam" tertutup, dan datanya masih bisa dipulihkan.
- **`JSON_PRETTY_PRINT` dilepas.** Berkas ini dibaca mesin; pretty-print membuatnya 2,3x lebih besar tanpa manfaat, sementara setiap penyimpanan menulis ulang semuanya.

Migrasi kunci lama juga diperkuat: pemeriksaannya diulang **di dalam** kunci eksklusif, sehingga dua tab yang membuka aplikasi berbarengan di browser baru tidak saling menimpa hasil migrasi.

Ini menutup T-7 dan mengambil bagian paling mendesak dari poin 5. Yang belum: batas ukuran payload (S-2) dan konvergensi dua arah lintas perangkat (T-6) — keduanya lebih rapi diselesaikan bersamaan dengan pemindahan ke basis data.

---

## 6. Perbaikan Lanjutan — Poin 4 & 6

### 6.1 Inisialisasi menjadi idempoten

**Menutup T-10 dan T-11.**

Ketiga listener tingkat dokumen — klik di luar panel, `scroll`, dan `resize` — dipindah ke `_bindGlobalListeners()` (`ColumnSettingsManager.js:522`) yang dijaga satu penanda modul. Ketiganya hanya menyentuh `_activeGridId`, jadi satu set memang cukup untuk seluruh grid di halaman. Sebelumnya didaftarkan ulang tiap kali sebuah grid diinisialisasi: tiga halaman memuat dua grid sekaligus, dan re-init apa pun menambah satu set lagi tanpa pernah melepas yang lama.

`_injectButton()` kini berhenti lebih awal bila `#colvis_trigger_<gridId>` sudah ada. Tombol yang sudah terpasang tetap benar — handler-nya membaca `_states[gridId]` yang baru saja diperbarui — jadi tidak perlu dibangun ulang. Bila grid dihancurkan dan dibuat lagi, tombolnya ikut hilang bersama DOM grid dan penyuntikan berjalan normal.

Penghapusan class `d-flex` milik aplikasi dibuang. Alasannya dulu masuk akal — `wrap` memakai `float:right`, dan float diabaikan di dalam container flex — tetapi menyelesaikannya dengan mengubah tata letak modul demi menempatkan satu tombol adalah harga yang salah. Sekarang `wrap` memakai class `.colvis-wrap`, dan varian `--float` hanya dipasang ketika wadahnya memang blok biasa.

### 6.2 Grid tanpa toolbar tetap dapat tombolnya

**Menutup T-12.**

`_resolveToolbar()` (`ColumnSettingsManager.js:546`) memakai `#t_<gridId>` bila ada. Bila tidak — grid yang tidak menyalakan `toolbar: [true,"top"]`, saat ini hanya `useracl/form.php` — ia membuat `.colvis-fallback-bar` tepat di atas `.ui-jqgrid`, sekali saja dan dipakai ulang pada init berikutnya. Sebelumnya kasus ini hanya menghasilkan `console.warn` dan tombolnya tidak pernah muncul.

Dengan ini seluruh **33 dari 33** grid bisa menampung panel, bukan lagi 32.

### 6.3 Validasi payload di server

**Menutup S-2.**

`sanitizePreferences()` (`GridPreference.php:284`) menjadi gerbang tunggal: yang tersimpan adalah hasil sanitasi, bukan kiriman mentah.

| Aturan | Nilai | Dasar |
|---|---|---|
| Pola `grid_name` | `[A-Za-z0-9._-]{1,120}` | Menampung kunci baru dan kunci lama ber-underscore; menolak `../`, garis miring, null byte, spasi |
| Pola nama kolom | `[A-Za-z0-9._-]{1,100}` | Seluruh **97** nama kolom yang dipakai aplikasi masuk pola ini; terpanjang 26 karakter |
| Maksimum kolom per grid | 200 | Grid terbanyak punya 25 kolom |
| Maksimum grid per user | 200 | Pemakaian normal mentok di 33 |
| Rentang `width` | 1–5000 | — |

Tiga keputusan yang perlu dicatat:

- **`order` dihitung ulang dari posisi**, setelah pengurutan berdasarkan `order` yang dikirim. Nilai dari klien tidak pernah menjadi sumber kebenaran, dan yang tersimpan selalu 0..n-1.
- **`width` di luar rentang dibuang, bukan membatalkan seluruh payload.** Satu angka aneh tidak boleh menghapus tata letak yang sudah benar; kolomnya tetap tersimpan tanpa `width` dan jatuh ke lebar bawaan.
- **`updatedAt` diambil dari jam server**, bukan dari kiriman klien. Klien tidak boleh mengklaim stempel waktu masa depan agar selalu menang ketika konvergensi lintas perangkat (T-6) dipasang nanti.

Bila jumlah grid melewati batas, `pruneExcessGrids()` membuang yang paling lama tidak disentuh dan tidak pernah membuang grid yang sedang disimpan. Batas ini hanya menggigit kunci yang dibuat-buat; pada 33 grid ia tidak pernah aktif.

Ketiga endpoint kini menolak `grid_name` yang tidak lolos pola — termasuk `load` dan `delete`, bukan hanya `save`.

### 6.4 Escaping di panel

**Menutup S-3.**

Dua perubahan di `_buildItemHTML()`:

- Nama kolom tidak lagi disisipkan sebagai literal ke dalam atribut `onclick`/`onchange`. Ternyata `_onToggle` dan `_onFreezeToggle` **tidak pernah memakai** argumen itu — keduanya membaca state dari `closest('.colvis-item')` — jadi argumennya dihapus dan permukaan injeksinya hilang tanpa perubahan perilaku sama sekali.
- Apa pun yang masuk ke HTML dilewatkan `_esc()` lebih dulu: `data-col` maupun teks label, termasuk pada baris kolom sistem.

Sanitasi di Bagian 6.3 sudah menjadi gerbang utamanya, tetapi panel tidak boleh bergantung pada itu. Pemeriksaan menyeluruh memastikan tidak ada lagi interpolasi `col.name`, `c.name`, atau `_getLabel()` yang lolos tanpa escape.

---

## 7. Verifikasi

**PHPUnit — 202 tes, 503 asersi, seluruhnya lulus** (termasuk 57 tes baru untuk fitur ini).

`GridPrefScopeTest` menegakkan inti poin 1 langsung terhadap `Routes.php` yang sesungguhnya: keenam ejaan URL runtuh ke satu scope.

| URL | Scope |
|---|---|
| `piutangemkl`, `Piutangemkl`, `piutangemkl/index`, `Piutangemkl/index` | `piutangemkl.index` |
| `tracing`, `tracing/index` | `tracing.index` |
| `cabang/form/MDN/edit`, `cabang/form/SBY/edit` | `cabang.form` — segmen dinamis tidak bocor |

Kelas tes yang sama juga mengunci satu jebakan yang nyaris terlewat: scope dipancarkan lewat `esc($scope, 'js')`, dan Laminas Escaper mengubah karakter di luar daftar amannya menjadi `\xHH`. Titik kebetulan termasuk yang dipertahankan — tetapi itu properti pustaka pihak ketiga, bukan jaminan, jadi sekarang ada tes yang menjaganya.

`GridPreferenceKeyTest` mengunci pemetaan kunci lama → baru, termasuk kasus yang **tidak boleh** cocok: grid modul lain, `gridId` berbeda pada halaman yang sama (`jqGrid` vs `jqGridAcl`), dan method non-`index` yang tidak boleh jatuh ke controller saja.

Logika JS diuji terpisah dengan harness Node (19 asersi, seluruhnya lulus) yang memuat `GridPreferenceManager.js` apa adanya di atas stub `localStorage` dan `jQuery`. Yang ditegakkan: nama kolom basi dibuang, kolom baru masuk di posisi alaminya, `order` dinormalkan, kolom sistem tidak pernah ikut tersimpan, `preserveOrder` menjaga urutan sambil tetap menerapkan lebar, dan migrasi `localStorage` memindahkan kunci lama tanpa menyentuh milik modul lain.

Satu catatan dari proses ini: pada preferensi **parsial** — hanya sebagian kolom punya `order` tersimpan — kolom tanpa preferensi memang menang posisi atas kolom bersimpanan bila ia berada lebih kiri di `colModel`. Itu perilaku yang disengaja: kolom yang baru dipasang developer di paling kiri harus muncul di paling kiri. Preferensi parsial sendiri hanya mungkin muncul dari data lama, karena serializer selalu menulis seluruh kolom.

`GridPreferenceStorageTest` menjaga lapisan penyimpanan: mengubah satu grid tidak menyentuh grid lain, mutator yang mengembalikan `null` benar-benar tidak menulis, berkas rusak dikarantina dengan payload aslinya utuh — baik dari jalur baca maupun jalur tulis — dan keluarannya tetap compact.

`GridPreferenceValidationTest` (32 tes) menutup gerbang validasi: whitelist `grid_name` menolak path traversal, garis miring, null byte, spasi, dan tipe non-string; payload cacat ditolak seluruhnya — termasuk nama kolom bermuatan markup, yang merupakan sumber S-3; `width` di luar rentang dibuang tanpa membatalkan sisanya; flag dipaksa boolean; `order` dihitung ulang dari posisi; nama ganda diambil yang pertama; dan pemangkasan kelebihan grid membuang yang terlama tanpa pernah menyentuh grid yang sedang disimpan.

Satu keterbatasan yang perlu disebut jujur: **escaping di sisi klien (Bagian 6.4) tidak punya tes otomatis.** Proyek ini tidak memuat `package.json` maupun `jsdom`, jadi panel yang bergantung DOM tidak bisa dirender di harness. Yang menjaganya adalah pemeriksaan menyeluruh atas seluruh interpolasi ke HTML, ditambah whitelist server yang sudah teruji dan menolak nama kolom bermuatan markup sebelum sempat tersimpan.

---

## 8. Sisa Pekerjaan

Berurut menurut kepentingan.

**Konvergensi lintas perangkat (T-5, T-6).** Ini satu-satunya cacat yang tersisa dan berdampak pada user. `load()` di klien hanya membaca server ketika `localStorage` kosong, dan preferensi yang datang dari server tidak pernah diterapkan ke grid yang sudah terlanjur dirender. Akibatnya perubahan di perangkat A tidak pernah sampai ke perangkat B, dan di browser baru tata letak bawaan bisa menimpa preferensi asli. `updatedAt` yang kini berasal dari jam server sudah menjadi penengah konfliknya; yang kurang adalah aturan konvergensi dan penerapan ulang setelah muat.

**Pindah penyimpanan ke basis data.** Race dan pemusnahan berkas sudah ditutup (Bagian 5.3), batas ukuran sudah ada (Bagian 6.3), jadi ini bukan lagi soal keselamatan melainkan efisiensi: satu tabel `tblgridpref(userid, grid_key, payload, updated_at)` dengan indeks unik menghapus amplifikasi tulis 31x dan sekaligus menyediakan tempat alami untuk aturan konvergensi di atas.

**Sisanya:** T-9 (drag header bawaan belum tersimpan), T-13 (`setTimeout(300)`), T-14 (lebar desktop bocor ke mobile), T-15 (fallback `'guest'`), S-4 (izin `mkdir` dan response mentah).

---

## 9. Rencana Rollout

Seluruh bloker sudah tertutup dan hambatan tahap lanjut sudah dibereskan, jadi keempat tahap bisa dijalankan. Cukup tambahkan `gridPreference: true` pada opsi jqGrid tiap view.

| Tahap | Modul | Jumlah | Alasan urutan |
|---|---|---|---|
| 1 | `omsetmarketing*` dan `omsetrekapmarketing*` | 12 | Sudah memakai `frozen: true`, jadi jalur freeze ikut teruji lebih dulu |
| 2 | `trucking*`, `overtopemkl*`, `supirpercabang`, `tracing`, `newshipper*` | 14 | Grid standar, tanpa kekhususan |
| 3 | `menu`, `roles`, `user`, `useracl/index`, `parameter` | 5 | Halaman dua-grid — aman setelah listener global dijadikan sekali daftar |
| 4 | `truckingtradoluardetailexp`, `useracl/form` | 2 | Keduanya kasus khusus dan keduanya sudah ditangani: yang pertama memakai `colNames` sehingga urutan kolom dinonaktifkan, yang kedua tanpa toolbar sehingga memakai bilah cadangan |

Tetap disarankan bertahap, bukan sekaligus — bukan karena ada yang menahan, melainkan supaya bila ada kejutan di satu grup, cakupannya kecil dan mudah ditelusuri. Yang layak diperhatikan saat tahap 1 berjalan: perilaku freeze pada grid ber-`frozen: true`, karena itu satu-satunya jalur yang belum pernah dipakai di produksi.

Konvergensi lintas perangkat sebaiknya menyusul sebelum banyak user berpindah-pindah perangkat, tetapi ia tidak menahan rollout: pada satu perangkat, preferensi sudah tersimpan dan terbaca dengan benar.
