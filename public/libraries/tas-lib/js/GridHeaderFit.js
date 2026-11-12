/**
 * GridHeaderFit.js - teks header kolom jqGrid tidak pernah terpotong.
 *
 * Header dibungkus per kata seperti Wrap Text di Excel (aturan CSS-nya ada di
 * styles.css, cari "HEADER WRAP"). Untuk tiap kolom yang terlihat dihitung
 * lebar terkecil agar labelnya muat dalam dua baris, mis. "SELISIH / (HARI)".
 * Bila kolom lebih sempit dari itu, kolom dilebarkan; lebar itu juga dijadikan
 * batas minimum (colModel.minResizeWidth) sehingga user tidak bisa menggeser
 * kolom sampai teks headernya terpotong.
 *
 * Dijalankan setelah grid dibuat, setelah font selesai dimuat (lebar teks baru
 * akurat saat itu), tiap data selesai dimuat, dan saat kolom ditampilkan.
 * Pelebaran tidak memicu resizeStop, jadi preferensi kolom user tidak ditimpa.
 *
 * Grid forceFit dilewati: di sana melebarkan satu kolom mengecilkan kolom
 * sebelahnya.
 */
(function ($) {
    'use strict';
    if (!$ || !$.fn.jqGrid) return;

    var SORT_ICON_WIDTH = 20; // ruang panah sort, muncul saat kolom diurutkan

    function px(v) {
        return parseFloat(v) || 0;
    }

    /** Teks label tanpa ikon sort. */
    function labelText(div) {
        var text = '';
        for (var n = div.firstChild; n; n = n.nextSibling) {
            if (n.nodeType === 1 && n.classList.contains('s-ico')) continue;
            text += n.textContent;
        }
        return text.replace(/\s+/g, ' ').trim();
    }

    /**
     * Lebar th (border-box) minimum supaya label muat dalam paling banyak
     * MAX_LINES baris tanpa memecah kata (seperti Wrap Text di Excel).
     */
    function requiredWidth(th, div, sortable) {
        var words = labelText(div).split(' ').filter(Boolean);
        if (!words.length) return 0;

        // Ukur di dalam div supaya font, uppercase, dan letter-spacing ikut.
        var probe = document.createElement('span');
        probe.style.cssText = 'position:absolute;visibility:hidden;white-space:pre;left:0;top:0;';
        div.appendChild(probe);
        var measure = function (s) {
            probe.textContent = s;
            return probe.getBoundingClientRect().width;
        };
        var space = measure('a b') - measure('ab');
        var widths = words.map(measure);
        div.removeChild(probe);

        // Div header ber-display flex: panah sort berdiri di samping blok teks,
        // jadi lebarnya mengurangi kedua baris.
        var icon = sortable ? SORT_ICON_WIDTH : 0;

        // Dua baris: cari titik potong yang membuat baris terpanjang sependek mungkin.
        var total = widths.reduce(function (a, b) { return a + b; }, 0) + space * (words.length - 1);
        var content = total;
        var first = 0;
        for (var k = 0; k < words.length - 1; k++) {
            first += widths[k] + (k ? space : 0);
            var rest = total - first - space;
            content = Math.min(content, Math.max(first, rest));
        }
        content += icon;

        var dcs = getComputedStyle(div);
        var chrome = th.offsetWidth - div.offsetWidth; // padding + border th
        return Math.ceil(content + px(dcs.paddingLeft) + px(dcs.paddingRight) + chrome) + 1;
    }

    /**
     * Samakan lebar baris layout header grup (tr.jqg-first-row-header) dengan lebar kolom
     * sebenarnya.
     *
     * Grid dengan setGroupHeaders punya baris tersembunyi ini di tabel header. Karena tabel
     * memakai table-layout: fixed, baris pertama itulah yang menentukan lebar semua kolom
     * header. jqGrid hanya memperbaruinya lewat event jqGridResizeStop, sedangkan pelebaran
     * di sini sengaja tidak memicu event itu. Akibatnya lebar kolom header tertinggal dari
     * body dan footer, dan garis batas kolom tidak sejajar.
     */
    function syncGroupHeaderRow(ts) {
        var view = $(ts).closest('.ui-jqgrid-view')[0];
        var row = view && view.querySelector('.ui-jqgrid-hdiv tr.jqg-first-row-header');
        if (!row) return;

        var cells = row.children;
        for (var i = 0; i < ts.p.colModel.length && i < cells.length; i++) {
            var h = ts.grid.headers[i];
            if (!h || !h.width) continue;

            var want = h.width + 'px';
            if (cells[i].style.width !== want) cells[i].style.width = want;
        }
    }

    function fit(ts) {
        var g = ts && ts.grid;
        var p = ts && ts.p;
        if (!g || !p || p.forceFit === true || g.resizing) return;

        var $ts = $(ts);
        var changed = false;

        for (var i = 0; i < p.colModel.length; i++) {
            var cm = p.colModel[i];
            var h = g.headers[i];
            if (!h || !h.el || cm.hidden === true || h.el.offsetWidth === 0) continue;

            var div = h.el.querySelector('div.ui-th-div');
            if (!div) continue;

            // Ukur ulang hanya bila teks header berubah (mis. setLabel).
            var label = div.textContent;
            if (cm._hdrFitLabel !== label || !cm._hdrFitWidth) {
                var req = requiredWidth(h.el, div, p.sortable !== false && cm.sortable !== false);
                if (!req) continue;
                cm._hdrFitLabel = label;
                cm._hdrFitWidth = req;
            }

            var need = cm._hdrFitWidth;
            // jqGrid menolak lebar <= minResizeWidth saat digeser.
            if (!(parseInt(cm.minResizeWidth, 10) >= need - 1)) cm.minResizeWidth = need - 1;

            if (h.width < need) {
                $ts.jqGrid('resizeColumn', i, need);
                changed = true;
            }
        }

        // Selalu disinkronkan, bukan hanya saat ada pelebaran: setGroupHeaders bisa dipanggil
        // setelah pelebaran terakhir dan menyalin lebar yang sudah usang.
        syncGroupHeaderRow(ts);

        if (changed && typeof ts.applyStickyFrozenColumns === 'function') {
            ts.applyStickyFrozenColumns();
        }
    }

    function hook(ts) {
        if (!ts || !ts.grid || ts._hdrFitHooked) return;
        ts._hdrFitHooked = true;

        var run = function () { fit(ts); };
        var remeasure = function () {
            ts.p.colModel.forEach(function (cm) { cm._hdrFitWidth = 0; });
            fit(ts);
        };

        $(ts).on('jqGridAfterGridComplete.hdrFit jqGridShowHideCol.hdrFit', run);
        run();
        if (document.fonts && document.fonts.ready) {
            document.fonts.ready.then(remeasure);
        }
    }

    var inner = $.fn.jqGrid;
    $.fn.jqGrid = function () {
        var res = inner.apply(this, arguments);
        if (arguments.length > 0 && typeof arguments[0] === 'object') {
            this.each(function () { hook(this); });
        }
        return res;
    };
    Object.assign($.fn.jqGrid, inner);

    window.GridHeaderFit = {
        fit: function (selector) {
            $(selector).each(function () { fit(this); });
        }
    };
})(jQuery);
