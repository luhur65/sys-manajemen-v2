/**
 * GridAutoHeight
 * ─────────────────────────────────────────────────────────────────────────────
 * Membungkus $.fn.jqGrid supaya grid yang menyalakan `autoHeight: true`
 * selalu setinggi DEFAULT_ROWS (13) baris utuh, di semua ukuran layar.
 *
 *   height: 400,       // hanya dipakai sebelum ada baris (belum bisa menghitung tinggi baris)
 *   autoHeight: true,  // tinggi = 13 baris utuh untuk SEMUA grid
 *
 * Tinggi selalu kelipatan tinggi baris (ditambah scrollbar horizontal bila ada)
 * supaya tidak ada baris yang terpotong dan PageUp/PageDown mendarat rapi.
 *
 * Perilaku di layar yang lebih pendek dari grid + elemen sekitarnya diatur FIT_MODE:
 *   'always' (default) grid mengikuti sisa ruang layar di desktop, antara 10 dan 13 baris.
 *            Layar pendek, laptop kecil, dan browser yang di-zoom sama-sama tertangani karena
 *            yang dihitung adalah tinggi viewport efektif, bukan tingkat zoom-nya.
 *   'never'  grid tetap 13 baris, halaman di-scroll seperti biasa
 *   'zoom'   seperti 'never', tetapi saat zoom terdeteksi (rasio outerWidth/innerWidth > 1.05)
 *            grid mengikuti sisa layar. Tidak andal: sebagian browser Chromium (mis. Opera,
 *            Edge) ikut menskalakan outerWidth sehingga zoom tidak terdeteksi.
 * Saat mengikuti layar, tinggi akhir = sisa ruang layar di bawah posisi body grid
 * (dikurangi baris total grid, info/pager, padding & margin pembungkus, serta footer
 * halaman), dibatasi antara MIN_ROWS (10) dan DEFAULT_ROWS (13) baris. Grid tidak pernah
 * lebih pendek dari 10 baris; bila sisa layar tidak cukup, halaman di-scroll.
 *
 * Dihitung ulang saat: grid selesai memuat data, jendela di-resize, halaman
 * selesai dimuat (font/gambar bisa menggeser tata letak), sidebar dibuka/ditutup,
 * dan area di sekitar grid berubah ukuran (ResizeObserver).
 *
 * Grid di dalam modal/lookup sengaja tidak memakai ini: cukup tidak menyalakan
 * `autoHeight`.
 */
(function($) {
    if (!$.fn.jqGrid) return;

    var previousJqGrid = $.fn.jqGrid;

    // 'never' | 'zoom' | 'always' -- lihat penjelasan di atas.
    var FIT_MODE = 'always';
    // Rasio outerWidth/innerWidth di atas ini dianggap browser sedang di-zoom lebih dari 100%.
    // Ambang 1.05 menyisihkan tepi jendela (±1%) dan menangkap zoom terkecil (110%).
    var ZOOM_RATIO_THRESHOLD = 1.05;
    // Jarak aman di bawah footer halaman supaya tidak muncul scrollbar karena selisih pembulatan.
    // Hanya dipakai saat grid mengikuti sisa ruang layar.
    var BOTTOM_GAP = 10;
    // Jumlah baris utuh yang terlihat pada tinggi default; sama untuk semua grid.
    var DEFAULT_ROWS = 13;
    // Jumlah baris utuh minimum saat grid mengikuti sisa ruang layar: kurang dari ini
    // halaman di-scroll daripada grid diperas lebih pendek.
    var MIN_ROWS = 10;
    // Di bawah lebar ini (breakpoint "md" Bootstrap) tata letak ponsel dipakai.
    var PHONE_MAX_WIDTH = 768;

    function px($el, prop) {
        return parseFloat($el.css(prop)) || 0;
    }

    /**
     * Browser sedang di-zoom lebih dari 100%?
     * Di Chrome/Edge outerWidth dalam piksel perangkat tanpa zoom, sedangkan innerWidth dalam
     * piksel CSS yang sudah terkena zoom, jadi rasionya sama dengan tingkat zoom (130% -> 1.3).
     * Docked DevTools atau panel samping juga memperkecil innerWidth sehingga ikut terdeteksi;
     * itu aman karena hasilnya hanya grid yang menyesuaikan sisa ruang layar.
     * Firefox melaporkan keduanya terkena zoom, jadi zoom tidak terdeteksi di sana. Sebagian browser
     * Chromium lain (diuji pada browser pengguna di zoom 130%) juga tidak terdeteksi, karena itu
     * mode 'zoom' bukan default; hanya dipakai bila FIT_MODE = 'zoom'.
     */
    function isBrowserZoomedIn() {
        return window.outerWidth > 0 && window.innerWidth > 0 &&
            (window.outerWidth / window.innerWidth) > ZOOM_RATIO_THRESHOLD;
    }

    /** Total tinggi semua yang berada di bawah body grid sampai ujung halaman. */
    function spaceBelow($bdiv) {
        var stop = $('.content-wrapper').get(0);
        var below = 0;
        var $el = $bdiv;

        while ($el.length && $el[0] !== stop && !$el.is('body')) {
            $el.nextAll().each(function() {
                var $s = $(this);
                var position = $s.css('position');
                if ($s.is(':visible') && position !== 'absolute' && position !== 'fixed') {
                    below += $s.outerHeight(true);
                }
            });

            var $parent = $el.parent();
            if (!$parent.length || $parent[0] === stop || $parent.is('body')) break;

            below += px($parent, 'padding-bottom') + px($parent, 'border-bottom-width') + px($parent, 'margin-bottom');
            $el = $parent;
        }

        if (stop) below += px($(stop), 'padding-bottom');

        var $footer = $('.main-footer').first();
        if ($footer.length && $footer.css('position') !== 'fixed') {
            below += $footer.outerHeight(true) || 0;
        }
        return below;
    }

    function fit($grid, declaredHeight) {
        var $gbox = $grid.closest('.ui-jqgrid');
        var $bdiv = $gbox.find('.ui-jqgrid-bdiv').first();
        if (!$bdiv.length || !$gbox.is(':visible')) return;

        // `chrome` = tinggi scrollbar horizontal yang ikut dihitung dalam tinggi bdiv.
        // Tinggi baris baru diketahui setelah ada baris; sebelum itu pakai `height` deklarasi grid.
        var rowEl = $grid.find('tr.jqgrow').get(0);
        var rowHeight = rowEl ? rowEl.offsetHeight : 0;
        var chrome = rowHeight > 0 ? $bdiv[0].offsetHeight - $bdiv[0].clientHeight : 0;

        // Tinggi default = DEFAULT_ROWS baris utuh, sama untuk semua grid.
        var maxHeight = rowHeight > 0 ? DEFAULT_ROWS * rowHeight + chrome : declaredHeight;

        var height;
        var fitToViewport = FIT_MODE === 'always' || (FIT_MODE === 'zoom' && isBrowserZoomedIn());
        // Tanpa baris, tinggi baris belum diketahui sehingga MIN_ROWS belum bisa dihitung:
        // pakai tinggi deklarasi dulu, dihitung ulang setelah data dimuat.
        if (!fitToViewport || rowHeight === 0 || $(window).width() < PHONE_MAX_WIDTH) {
            height = maxHeight;
        } else {
            var minHeight = MIN_ROWS * rowHeight + chrome;
            var available = Math.floor($(window).height() - $bdiv.offset().top - spaceBelow($bdiv) - BOTTOM_GAP);
            height = Math.max(minHeight, Math.min(maxHeight, available));

            // Bulatkan ke kelipatan tinggi baris supaya tidak ada baris yang terpotong di tepi bawah
            // dan PageUp/PageDown (setCustomBindKeys) selalu mendarat tepat di tepi area terlihat.
            height = Math.floor((height - chrome) / rowHeight) * rowHeight + chrome;
        }

        if (Math.abs($bdiv.outerHeight() - height) > 1) {
            $grid.jqGrid('setGridHeight', height);
            // styles.css memaksa tinggi semua grid di layar < 768px (height: ... !important);
            // tinggi inline biasa dari jqGrid kalah, jadi ditimpa dengan !important inline.
            $bdiv[0].style.setProperty('height', height + 'px', 'important');
        }
    }

    function attach($grid, maxHeight) {
        var ns = '.gridAutoHeight_' + String($grid.attr('id') || 'grid').replace(/[^\w-]/g, '_');
        var timer = null;
        var schedule = function(wait) {
            clearTimeout(timer);
            timer = setTimeout(function() { fit($grid, maxHeight); }, wait);
        };

        $(window).off('resize' + ns + ' load' + ns)
            .on('resize' + ns, function() { schedule(100); })
            .on('load' + ns, function() { schedule(0); });
        $(document).off('collapsed.lte.pushmenu' + ns + ' shown.lte.pushmenu' + ns)
            .on('collapsed.lte.pushmenu' + ns + ' shown.lte.pushmenu' + ns, function() { schedule(350); });
        $grid.off('jqGridAfterLoadComplete' + ns)
            .on('jqGridAfterLoadComplete' + ns, function() { schedule(0); });

        // Elemen di sekitar grid sering berubah tinggi belakangan (baris info "View x - y of z",
        // tombol export, dsb.), sehingga hasil hitungan lama jadi basi. Hitung ulang tiap kali
        // pembungkus grid atau area konten berubah ukuran. Hasil hitungan tidak bergantung pada
        // tinggi body grid itu sendiri, jadi tidak terjadi loop.
        if (window.ResizeObserver) {
            var previousObserver = $grid.data('gridAutoHeightObserver');
            if (previousObserver) previousObserver.disconnect();

            var observer = new ResizeObserver(function() { schedule(50); });
            var watch = [$grid.closest('.ui-jqgrid').parent().get(0)];
            $('.content-wrapper').first().children().each(function() { watch.push(this); });
            watch.forEach(function(el) { if (el) observer.observe(el); });
            $grid.data('gridAutoHeightObserver', observer);
        }

        // Pas-kan sekali setelah skrip halaman selesai memasang filter toolbar dsb.,
        // yang menambah tinggi header di atas body grid.
        schedule(0);
    }

    $.fn.jqGrid = function() {
        var result = previousJqGrid.apply(this, arguments);

        var options = arguments[0];
        if (options && typeof options === 'object' && options.autoHeight === true) {
            var maxHeight = parseFloat(options.height);
            if (maxHeight > 0) {
                this.each(function() { attach($(this), maxHeight); });
            }
        }
        return result;
    };

    // Copy semua properti static milik jqGrid sebelumnya (seperti jqGrid.extend dll)
    Object.assign($.fn.jqGrid, previousJqGrid);
})(jQuery);
