/**
 * GridLiveResize.js - geser lebar kolom jqGrid secara halus (live).
 *
 * Bawaan jqGrid 5.9 (grid.base.js: dragStart/dragMove/dragEnd):
 *   1. Selama digeser hanya garis penanda (#rs_m<id>) yang bergerak; lebar kolom
 *      baru diterapkan sekali saat mouse dilepas -> kolom terlihat "loncat".
 *   2. mousemove hanya dipasang di header (hDiv). Begitu kursor turun ke baris
 *      data atau keluar grid, garis berhenti mengikuti -> terasa patah-patah.
 *
 * File ini tidak mengubah inti jqGrid. Ia mendengarkan mouse di level document
 * (fase capture, karena handler jqGrid me-return false) lalu menerapkan lebar
 * kolom tiap frame (requestAnimationFrame). Saat mouse dilepas, dragEnd bawaan
 * tetap berjalan: colModel diperbarui dan resizeStop / jqGridResizeStop tetap
 * terpicu, jadi penyimpanan preferensi kolom tidak terpengaruh.
 *
 * Tidak aktif untuk grid forceFit atau RTL (pakai perilaku bawaan).
 */
(function () {
    'use strict';

    var active = null; // { ts, tables, markerHidden }
    var rafId = 0;
    var lastX = 0;

    function findGridTable(target) {
        var view = target.closest('.ui-jqgrid-view');
        var tbl = view && view.querySelector('.ui-jqgrid-bdiv table.ui-jqgrid-btable');
        return tbl && tbl.grid && tbl.p ? tbl : null;
    }

    function firstTable(div) {
        return div ? div.querySelector('table') : null;
    }

    function apply() {
        rafId = 0;
        if (!active) return;

        var ts = active.ts;
        var g = ts.grid;
        var p = ts.p;
        var r = g.resizing;
        if (!r || p.forceFit === true || p.direction !== 'ltr') return;

        var idx = r.idx;
        var h = g.headers[idx];

        // Tahan di lebar minimum, jangan berhenti mengikuti kursor.
        var diff = lastX - r.startX;
        var minDiff = r.minW + 1 - h.width;
        if (diff < minDiff) diff = minDiff;

        var width = h.width + diff;
        var tblWidth = p.tblwidth + diff;

        // Nilai inilah yang nanti dipakai dragEnd bawaan saat mouse dilepas.
        h.newWidth = width;
        g.newWidth = tblWidth;

        if (!active.tables) {
            active.tables = [firstTable(g.bDiv), firstTable(g.hDiv)];
            if (p.footerrow) active.tables.push(firstTable(g.sDiv));
            if (p.headerrow) active.tables.push(firstTable(g.hrDiv));
        }
        if (!active.markerHidden && g.curGbox) {
            // Tepi kolom sendiri sudah ikut bergerak; garis penanda tidak perlu.
            g.curGbox.css('display', 'none');
            active.markerHidden = true;
        }

        var w = width + 'px';
        h.el.style.width = w;
        if (g.cols[idx]) g.cols[idx].style.width = w;
        if (g.footers.length > 0 && g.footers[idx]) g.footers[idx].style.width = w;
        if (g.hrheaders.length > 0 && g.hrheaders[idx]) g.hrheaders[idx].style.width = w;

        var tw = tblWidth + 'px';
        for (var i = 0; i < active.tables.length; i++) {
            if (active.tables[i]) active.tables[i].style.width = tw;
        }

        // Saat kolom dikecilkan di ujung kanan, scroll body ikut mundur.
        var sl = g.bDiv.scrollLeft;
        if (g.hDiv.scrollLeft !== sl) g.hDiv.scrollLeft = sl;
        if (p.footerrow && g.sDiv && g.sDiv.scrollLeft !== sl) g.sDiv.scrollLeft = sl;
        if (p.headerrow && g.hrDiv && g.hrDiv.scrollLeft !== sl) g.hrDiv.scrollLeft = sl;

        // Kolom beku (sticky) di kanannya perlu posisi left baru.
        if (typeof ts.applyStickyFrozenColumns === 'function' &&
            h.el.classList.contains('frozen-col-sticky')) {
            ts.applyStickyFrozenColumns();
        }
    }

    document.addEventListener('mousedown', function (e) {
        if (e.button !== 0 || !e.target.closest) return;
        if (!e.target.closest('th > span.ui-jqgrid-resize')) return;
        var ts = findGridTable(e.target);
        active = ts ? { ts: ts, tables: null, markerHidden: false } : null;
    }, true);

    document.addEventListener('mousemove', function (e) {
        if (!active || !active.ts.grid || !active.ts.grid.resizing) return;
        lastX = e.pageX;
        if (!rafId) rafId = requestAnimationFrame(apply);
    }, true);

    document.addEventListener('mouseup', function () {
        if (!active) return;
        // Terapkan posisi terakhir sebelum dragEnd bawaan (fase bubble) membacanya.
        if (rafId) {
            cancelAnimationFrame(rafId);
            apply();
        }
        active = null;
    }, true);
})();
