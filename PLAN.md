# Sys-Modern Module Migration Guidelines (Standard Rules)

Dokumen ini berisi standar baku (SOP) yang **WAJIB** diikuti untuk setiap migrasi modul dari versi *legacy* (`sys`) ke `sys-modern`. Standar ini dibuat berdasarkan pola arsitektur terbaik dari modul **Laporan Omset** (`Omset.php`) yang terbukti tangguh, efisien, dan bebas *bug*.

## 1. Arsitektur Controller
Semua controller harus menge-extend `BaseController` bawaan CodeIgniter 4.
*   **Routing Layout**: Karena `BaseController::render()` sudah secara otomatis merakit struktur *header*, *sidebar*, *navbar*, dan *footer*, maka pada file *View* (`index.php`), **dilarang keras** memanggil `<?= $this->extend(...) ?>` atau tag `<html>`. View hanya cukup membungkus konten dalam `<div class="container-fluid">`.
*   **Data API**: *Endpoint* untuk Grid (`grid()` atau `griddetail()`) harus merespons *request* AJAX `POST` yang berisi parameter *pagination* bawaan jqGrid (`page`, `rows`, `sidx`, `sord`) serta parameter *Filter Card* (`cabang`, `tgl_dari`, dsb).
*   **Response JSON**: Kembalikan data menggunakan `$this->response->setJSON($responce);`. Objek response harus memiliki `page`, `total`, `records`, `rows` (yang berisi id & cell array), dan opsional `userdata` untuk mencetak *Grand Total Footer*.

## 2. Arsitektur Model (Database)
Karena menggunakan SQL Server, query dasar harus dioptimalkan untuk performa.
*   **Pattern getTableName**: Gunakan fungsi *helper* `getTableName($cabang)` di dalam Model jika modul memisahkan tabel per cabang (contoh: `TradoLuarJkt`, `TradoLuarMdn`).
*   **Pagination SQL Server**: Fungsi `get()` wajib menggunakan teknik `ROW_NUMBER() OVER(ORDER BY ...)` sebagai standar *pagination* SQL Server (karena versi lama tidak mendukung sintaks `LIMIT ... OFFSET` seperti MySQL).
*   **String Formatting Tanggal**: Mengingat adanya *bug* format tanggal pada jqGrid saat melakukan *Lazy Loading* (di atas 50 baris pertama), maka **semua kolom tanggal WAJIB diformat di sisi backend**.
    *   Gunakan `date('d-M-Y', strtotime($row->FTgl))` di Controller/Model sebelum dikirim di JSON cell.
    *   Jangan gunakan tipe data *datetime* SQL untuk dikirim mentah ke Frontend.

## 3. UI/UX Frontend & Filter Card
UI wajib menggunakan elemen AdminLTE 3 (Bootstrap 4).
*   **Filter Card**: Gunakan *Card* (dapat dilipat/collapse) di atas grid untuk filter Global (seperti dropdown Cabang, Tgl Dari, Tgl Sampai).
*   **Select2**: Semua elemen *dropdown* pilihan ganda (seperti Cabang) harus diinisialisasi menggunakan *library* Select2 (`$('.select2bs4').select2({ theme: 'bootstrap4' });`).
*   **Tombol "Detail"**: Jika sistem *legacy* menggunakan tombol yang melempar user ke halaman/pop-up baru, **GANTI** menggunakan fitur bawaan `subGrid: true` pada jqGrid. Ini memberikan pengalaman UX yang jauh lebih halus.

## 4. Implementasi JqGrid & Lazy Loading (Wajib)
Semua Grid dengan potensi ribuan baris harus mengimplementasikan teknik *Infinite Scroll / Lazy Loading* menggunakan pustaka `lazyLoadingGridMonolith.js`.
*   **Konfigurasi Parameter**: 
    `datatype: 'local'` (BUKAN 'json').
*   **Trigger Initial Load**: Di bagian bawah konfigurasi jqGrid, jalankan fungsi:
    ```javascript
    if(typeof loadGridData === 'function') {
        loadGridData("#jqGrid", apiUrl, $grid.jqGrid('getGridParam', 'postData'), 1, rowNum, 'down', 'reload');
    }
    ```
*   **Server-Side Sorting**: Intersepsi aksi *sorting* klik kolom agar memicu `loadGridData` ke server, bukan sorting client-side:
    ```javascript
    onSortCol: function(index, iCol, sortorder) {
        if (typeof cachedData !== 'undefined') cachedData = {};
        if(typeof loadGridData === 'function') {
            loadGridData("#jqGrid", apiUrl, $grid.jqGrid('getGridParam', 'postData'), 1, $grid.jqGrid('getGridParam', 'rowNum'), 'jump', 'page');
        }
        return 'stop';
    }
    ```
*   **Pencarian Filter Toolbar**: Tangkap *event* `beforeSearch` agar bisa diselaraskan dengan parameter lazy loader:
    ```javascript
    beforeSearch: function() {
        var postData = $grid.jqGrid('getGridParam', 'postData');
        if (postData.filters) {
            var filtersObj = JSON.parse(postData.filters);
            postData._search = (filtersObj.rules && filtersObj.rules.length > 0);
        }
        $grid.jqGrid('setGridParam', { postData: postData });
        if (typeof cachedData !== 'undefined') cachedData = {};
        $grid.jqGrid('clearGridData');
        if(typeof loadGridData === 'function') {
            loadGridData("#jqGrid", apiUrl, $grid.jqGrid('getGridParam', 'postData'), 1, $grid.jqGrid('getGridParam', 'rowNum'), 'jump', 'page');
        }
        return false;
    }
    ```
*   **Grid ColModel Tanggal**: Mengingat tanggal di-format dari Backend, hilangkan properti `formatter: 'date'` dari frontend, namun tetap pertahankan `sorttype: 'date'` agar logika *filter toolbar* dapat bekerja semestinya.

## 5. Ringkasan Kriteria "Done" (Selesai)
Setiap migrasi modul dianggap selesai apabila telah memenuhi kriteria berikut:
1. Tidak ada error SQL syntax atau bug pencarian (khususnya untuk *Query Builder* `operationAll`).
2. Data yang ditampilkan saat *scroll* memuat halaman ke-2 dan seterusnya memiliki format tipe data (tanggal, uang) yang konsisten (tidak pecah/reset formatnya).
3. Mendukung integrasi Cabang-Spesifik dengan benar tanpa memuat modul berlipat ganda.
4. Tampilan rapi, responsif, menggunakan Bootstrap 4, FontAwesome, Select2, dan Lazy Loading JqGrid.
