# Panduan Pengembangan `sys-modern` untuk AI Agent

Dokumen ini berisi panduan, konteks, dan aturan ketat untuk AI Agent manapun yang melanjutkan pengembangan, perbaikan, atau migrasi proyek `sys-modern` (CodeIgniter 4). Proyek ini merupakan migrasi dari sistem lama berbasis CodeIgniter 3 (`sys`).

## 1. Stack Teknologi & Arsitektur
- **Framework**: CodeIgniter 4 (CI4).
- **Database**: SQL Server (Driver: `sqlsrv`).
- **Koneksi DB Utama**: Sebagian besar modul operasional (Trucking, Omset, dll.) menggunakan koneksi spesifik ke database *trucking*. Selalu gunakan `\Config\Database::connect('dbtruck')` di dalam Model CI4 kecuali modul tersebut murni menggunakan database *default*.

## 2. Aturan Fundamental (Strict Minimal-Diff)
- **Hanya lakukan perubahan yang secara eksplisit diminta oleh user.**
- **Jangan mengubah bagian kode lain yang tidak diminta.**
- Ikuti format kode, indentasi, *style*, dan struktur yang sudah ada di codebase.
- Jangan melakukan *refactor*, *rename* variabel, optimasi, atau *cleanup* kecuali diminta.
- Jika instruksi kurang jelas, selalu minta klarifikasi sebelum mengubah kode.
- Jangan membuat asumsi di luar permintaan (misal: membuat route, model, atau struktur baru tanpa izin/analisis).

## 3. Panduan Migrasi CI3 ke CI4
- **Routing**: CI4 pada proyek ini **tidak menggunakan Auto-Routing**. Setiap *Controller* dan fungsinya harus didaftarkan secara eksplisit di `app/Config/Routes.php` menggunakan `$routes->match(['GET', 'POST'], 'url', 'Controller::method');`. Daftarkan kombinasi *CamelCase* dan *lowercase* jika dibutuhkan oleh *view* legacy.
- **Model**: 
  - Pastikan untuk memeriksa referensi tabel/view di CI3. Jangan berasumsi dua *controller* yang menggunakan model yang sama di CI3 mengambil data dari tabel yang sama (Contoh kasus: `Overtopemkl` memanggil `get_where1()` untuk `LapEMKL_OverDue`, sedangkan `Piutangemkl` memanggil `get_where2()` untuk `LapEMKL_Piutang` meskipun keduanya di CI3 menggunakan `movertop.php`). Selalu pisahkan ke Model baru jika sumber datanya berbeda.
- **Query SQL Server**:
  - Untuk paginasi (menggantikan `LIMIT` dan `OFFSET`), proyek ini menggunakan teknik `ROW_NUMBER() OVER (ORDER BY col) AS RowNum` di dalam *subquery*, lalu memfilternya dengan `WHERE RowNum BETWEEN $start AND $sampai`.
  - Beberapa *stored procedure* seperti `usp_posisikolom` digunakan untuk mendapatkan nama kolom berdasarkan urutan index jqGrid.
  - Untuk menjumlahkan / format angka pada raw query, gunakan `TRY_CAST(REPLACE(col, ',', '') AS FLOAT)`.

## 4. UI/UX dan Front-End
- **jqGrid**: Sangat diandalkan untuk tabel data.
  - Format respon API dari CI4 untuk jqGrid harus me-return objek JSON berisi: `page`, `total` (total halaman), `records` (total baris), dan `rows` (array berisi `id` dan array `cell`).
  - **Paginasi & Pencarian**: Implementasikan paginasi di *backend* (jangan meload seluruh data ke *view* seperti `jsonstring` di CI3 kecuali secara eksplisit diminta). Parameter standar POST dari jqGrid: `page`, `rows` (limit), `sidx` (sort index), `sord` (sort order), `_search`, dan `filters`.
  - Fungsi `operationAll($filters)` biasanya disematkan di *Controller* untuk mem-parsing string JSON filter bawaan jqGrid menjadi klausa `WHERE`.
- **Modals & DOM**: Gunakan *ID selector* standar. Saat melakukan update *view*, pastikan tidak menghilangkan fungsi JavaScript/jQuery bawaan dari *template*.

## 5. Pengetahuan Spesifik Domain (Gotchas!)
- **`tblacl` (Access Control List)**: Tabel ini **tidak memiliki Auto Increment / Identity Column** pada *primary key* (`aclid`). Ketika melakukan penyisipan baris (*Insert*), *primary key* harus di-*generate* manual menggunakan query `ISNULL(MAX(aclid), 0) + 1`.
- **Handling Date**: SQL Server cukup ketat mengenai format tanggal. Seringkali diperlukan konversi string tanggal (misal input: `dd-mm-yyyy`) ke format SQL standar (`Y-m-d`) saat menyusun klausa `WHERE`.
- **`FlastUpdate`**: Beberapa modul memonitor tanggal pembaharuan terakhir dari tabel. Gunakan MAX(FlastUpdate) atau tabel history terkait bila diminta.

## 6. Prosedur Pengerjaan untuk AI Agent
1. **Analisa**: Jika user melaporkan *error* atau perbedaan data, baca file *controller* dan *model* legacy (CI3) terlebih dahulu di direktori `sys/` untuk memastikan struktur *query* aslinya.
2. **Kesesuaian**: Pastikan hasil akhir di `sys-modern/` sama persis secara fungsionalitas dan output datanya dengan sistem lama.
3. **Commit**: Saat mendaftarkan file atau mengubah *Routes*, perhatikan penggunaan kapitalisasi karena sistem beroperasi dalam kaidah *case-sensitive* (meski OS Windows mungkin mengabaikannya, standar CI4 memerlukan penulisan rute yang tepat).

---
*Gunakan dokumen ini sebagai kompas untuk mempertahankan konsistensi antara arsitektur CI3 lama dengan CI4 modern pada proyek ini.*
