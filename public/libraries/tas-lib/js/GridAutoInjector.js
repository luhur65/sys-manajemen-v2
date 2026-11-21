/**
 * GridAutoInjector
 * ─────────────────────────────────────────────────────────────────────────────
 * Membungkus $.fn.jqGrid supaya grid yang menyalakan `gridPreference: true`
 * otomatis mendapat: colModel hasil preferensi user, panel "Atur Kolom", dan
 * autosave lebar kolom.
 *
 * Soal kunci preferensi: kunci TIDAK lagi diturunkan dari
 * window.location.pathname. Satu halaman grid bisa dicapai lewat beberapa URL
 * -- Routes.php mendaftarkan 'Piutangemkl', 'piutangemkl', 'Piutangemkl/index'
 * dan 'piutangemkl/index' untuk controller yang sama -- dan pathname juga ikut
 * membawa base path aplikasi, sehingga preferensi user hilang begitu aplikasi
 * dipindah docroot. Sekarang kunci dibentuk dari scope yang dikirim server
 * (window.gridPrefScope = "controller.method", lihat partials/header.php),
 * menghasilkan kunci stabil "controller.method.gridId".
 */
(function($) {
    if (!$.fn.jqGrid) return;

    var originalJqGrid = $.fn.jqGrid;

    /**
     * Cadangan bila scope dari server tidak tersedia (mis. route berbasis
     * closure). Tetap dinormalkan: base path dibuang, huruf dikecilkan, dan
     * hanya dua segmen pertama yang dipakai supaya '/modul/detail/123' tidak
     * melahirkan kunci baru untuk tiap record.
     */
    function fallbackScope() {
        var path = window.location.pathname || '';
        try {
            var base = new URL(typeof appUrl !== 'undefined' ? appUrl : '/', window.location.origin).pathname;
            if (base && base !== '/' && path.toLowerCase().indexOf(base.toLowerCase()) === 0) {
                path = path.slice(base.length);
            }
        } catch (e) { /* pakai path apa adanya */ }

        var segs = path.split('/').filter(function(s) {
            return s.length && s.toLowerCase() !== 'index.php';
        }).map(function(s) {
            return s.toLowerCase();
        });

        if (!segs.length) return 'root.index';
        return segs[0] + '.' + (segs.length > 1 ? segs[1] : 'index');
    }

    function buildPrefKey(gridOptions, gridId) {
        // Modul boleh memaksa kunci sendiri lewat `gridPrefKey`, berguna kalau
        // satu grid dipakai ulang di beberapa controller.
        if (typeof gridOptions.gridPrefKey === 'string' && gridOptions.gridPrefKey) {
            return gridOptions.gridPrefKey;
        }
        var scope = (typeof window.gridPrefScope === 'string' && window.gridPrefScope)
            ? window.gridPrefScope
            : fallbackScope();
        return scope + '.' + gridId;
    }

    $.fn.jqGrid = function() {
        if (arguments.length > 0 && typeof arguments[0] === 'object') {
            var gridOptions = arguments[0];
            var $grid = this;
            var gridId = $grid.attr('id');

            if (gridId && gridOptions.colModel && gridOptions.gridPreference === true) {
                var prefKey = buildPrefKey(gridOptions, gridId);

                // 1. Simpan salinan murni dari colModel aslinya sebelum diganggu
                var baseColModel = JSON.parse(JSON.stringify(gridOptions.colModel));
                var savedPrefs = null;

                // Grid gaya lama memasangkan header lewat array `colNames`
                // terpisah, dipasangkan ke colModel per indeks. Menyusun ulang
                // colModel tanpa menyusun ulang colNames membuat judul kolom
                // tertukar, jadi untuk grid seperti itu preferensi urutan
                // dilewati -- lebar, sembunyi, dan freeze tetap diterapkan.
                var hasLegacyColNames = Array.isArray(gridOptions.colNames) && gridOptions.colNames.length > 0;

                // 2. Baca preferensi grid dari cache secara sinkron
                if (typeof GridPreferenceManager !== 'undefined') {
                    savedPrefs = GridPreferenceManager.loadSync(prefKey);
                    if (savedPrefs && savedPrefs.length > 0) {
                        if (hasLegacyColNames) {
                            console.warn('[GridPref] "' + gridId + '" memakai colNames; urutan kolom tidak diterapkan.');
                        }
                        gridOptions.colModel = GridPreferenceManager.apply(
                            gridOptions.colModel,
                            savedPrefs,
                            { preserveOrder: hasLegacyColNames }
                        );
                    }
                }

                // 3. Panggil jqGrid original dengan opsi yang sudah dimodifikasi
                var res = originalJqGrid.apply(this, arguments);

                // 4. Setelah grid selesai dirender (menggunakan timeout tipis)
                setTimeout(function() {
                    // Background sync dari server khusus untuk device baru (akan tercache untuk refresh berikutnya)
                    if (typeof GridPreferenceManager !== 'undefined') {
                        GridPreferenceManager.load(prefKey);
                    }

                    // Inject panel pengaturan kolom di toolbar
                    if (typeof ColumnSettingsManager !== 'undefined') {
                        ColumnSettingsManager.init('#' + gridId, prefKey, baseColModel, {
                            lockOrder: hasLegacyColNames
                        });
                    }
                    // Inject detektor event resizeStop agar width tersimpan otomatis
                    if (typeof GridPreferenceManager !== 'undefined') {
                        GridPreferenceManager.attach('#' + gridId, prefKey);
                    }
                }, 300);

                return res;
            }
        }

        // Pemanggilan method biasa (bukan inisialisasi)
        return originalJqGrid.apply(this, arguments);
    };

    // Copy semua properti static milik jqGrid asli (seperti jqGrid.extend dll)
    Object.assign($.fn.jqGrid, originalJqGrid);
})(jQuery);
