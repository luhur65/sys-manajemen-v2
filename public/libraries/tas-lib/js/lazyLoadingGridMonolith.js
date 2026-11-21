/**
 * lazyLoadingGridMonolith.js
 * Library Lazy Loading khusus untuk Monolith (PHP/CI)
 * Dioptimalkan dari logic lazyLoadingGrid.js terbaru.
 */

// ══════════════════════════════════════════════════════════════════════════════
// LazyGridIDB — IndexedDB shared layer
// ══════════════════════════════════════════════════════════════════════════════
//
// Peran IndexedDB di sini: tempat CADANGAN halaman yang sudah dibuang dari memori
// (lihat MAX_MEMORY_PAGES). Memori hanya memegang halaman di sekitar posisi user;
// saat user kembali ke halaman jauh, halaman itu dibaca dari IndexedDB alih-alih
// diminta ulang ke server.
//
// Aturan kunci & kebersihan:
//  - Setiap entri milik SATU instance halaman (PAGE_ID, acak per page load) dan
//    kuncinya memuat seluruh postData + sort + rows (lihat scopeKey). Dua tab
//    dengan filter berbeda tidak mungkin saling membaca.
//  - clearModule hanya menghapus entri milik instance ini.
//  - Entri lebih tua dari MAX_AGE_MS dibuang saat DB dibuka; entri milik instance
//    ini dibuang saat halaman ditutup (pagehide, best effort); seluruh DB dihapus
//    saat halaman login dibuka (logout / sesi habis), lihat login.php.
class LazyGridIDB {
    static _db = null;
    static _openPromise = null;
    static IDB_NAME    = 'jqgrid_cache';
    static IDB_VERSION = 1;
    static IDB_STORE   = 'pages';
    static MAX_AGE_MS  = 15 * 60 * 1000;

    static open() {
        if (LazyGridIDB._db) return Promise.resolve(LazyGridIDB._db);
        if (LazyGridIDB._openPromise) return LazyGridIDB._openPromise;
        LazyGridIDB._openPromise = new Promise((resolve, reject) => {
            if (typeof indexedDB === 'undefined') { reject(new Error('IndexedDB tidak tersedia')); return; }
            const req = indexedDB.open(LazyGridIDB.IDB_NAME, LazyGridIDB.IDB_VERSION);
            // Tanpa ini promise bisa menggantung selamanya dan grid berhenti memuat;
            // dengan reject, pemanggil jatuh ke server.
            req.onblocked = () => reject(new Error('IndexedDB terblokir'));
            req.onupgradeneeded = (e) => {
                const db = e.target.result;
                if (!db.objectStoreNames.contains(LazyGridIDB.IDB_STORE)) {
                    const store = db.createObjectStore(LazyGridIDB.IDB_STORE, { keyPath: 'key' });
                    store.createIndex('moduleName', 'moduleName', { unique: false });
                    store.createIndex('timestamp',  'timestamp',  { unique: false });
                }
            };
            req.onsuccess = (e) => {
                LazyGridIDB._db = e.target.result;
                LazyGridIDB._db.onversionchange = () => {
                    LazyGridIDB._db.close();
                    LazyGridIDB._db = null;
                    LazyGridIDB._openPromise = null;
                };
                LazyGridIDB.purgeExpired(LazyGridIDB._db);
                resolve(LazyGridIDB._db);
            };
            req.onerror = (e) => reject(e.target.error);
        }).catch((e) => {
            LazyGridIDB._openPromise = null;
            throw e;
        });
        return LazyGridIDB._openPromise;
    }

    /**
     * Buang entri kedaluwarsa (sisa tab yang sudah ditutup) dan entri format lama
     * tanpa `owner` -- kuncinya tidak memuat filter, jadi tidak boleh dibaca lagi.
     */
    static purgeExpired(db) {
        try {
            const tx = db.transaction(LazyGridIDB.IDB_STORE, 'readwrite');
            const batas = Date.now() - LazyGridIDB.MAX_AGE_MS;
            const req = tx.objectStore(LazyGridIDB.IDB_STORE).openCursor();
            req.onsuccess = (e) => {
                const cursor = e.target.result;
                if (!cursor) return;
                const v = cursor.value || {};
                const prev = LazyGridUtils.PREV_PAGE_ID;
                if (!v.owner || !(v.timestamp > batas) || (prev && v.owner === prev)) cursor.delete();
                cursor.continue();
            };
        } catch (e) { /* bukan hal kritis */ }
    }

    static tx(mode, callback) {
        return LazyGridIDB.open().then(db =>
            new Promise((resolve, reject) => {
                try {
                    const tx = db.transaction(LazyGridIDB.IDB_STORE, mode);
                    const store = tx.objectStore(LazyGridIDB.IDB_STORE);
                    callback(store, resolve, reject);
                    tx.onerror = (e) => reject(e.target.error);
                } catch (e) {
                    reject(e);
                }
            })
        );
    }

    static savePage(moduleName, pageNumber, data, filterKey) {
        const key = LazyGridUtils.buildCacheKey(moduleName, pageNumber, filterKey);
        const entry = { key, moduleName, owner: LazyGridUtils.PAGE_ID, pageNumber, filterKey, data, timestamp: Date.now() };
        return LazyGridIDB.tx('readwrite', (store, resolve) => {
            const req = store.put(entry);
            req.onsuccess = () => resolve();
            req.onerror = () => {
                LazyGridIDB.clearModule(moduleName).then(() =>
                    LazyGridIDB.tx('readwrite', (s2, res2) => {
                        const r2 = s2.put(entry);
                        r2.onsuccess = () => res2();
                        r2.onerror = () => res2();
                    })
                ).then(resolve).catch(resolve);
            };
        }).catch(e => console.warn('[IDB] savePage error:', e));
    }

    static loadPage(moduleName, pageNumber, filterKey, maxAgeMs) {
        const key = LazyGridUtils.buildCacheKey(moduleName, pageNumber, filterKey);
        return LazyGridIDB.tx('readonly', (store, resolve) => {
            const req = store.get(key);
            req.onsuccess = (e) => {
                const entry = e.target.result;
                if (!entry) { resolve(null); return; }
                if (Date.now() - entry.timestamp > maxAgeMs) {
                    LazyGridIDB.open().then(db =>
                        db.transaction(LazyGridIDB.IDB_STORE, 'readwrite')
                          .objectStore(LazyGridIDB.IDB_STORE).delete(key)
                    );
                    resolve(null);
                    return;
                }
                resolve(entry.data);
            };
            req.onerror = () => resolve(null);
        }).catch(() => null);
    }

    /**
     * Hapus entri milik instance halaman ini. moduleName null = semua modul.
     * Entri tab lain tidak disentuh: tab itu masih memakainya.
     */
    static clearModule(moduleName) {
        return LazyGridIDB.open().then(db =>
            new Promise(resolve => {
                const tx = db.transaction(LazyGridIDB.IDB_STORE, 'readwrite');
                const store = tx.objectStore(LazyGridIDB.IDB_STORE);
                const req = moduleName == null
                    ? store.openCursor()
                    : store.index('moduleName').openCursor(IDBKeyRange.only(moduleName));
                req.onsuccess = (e) => {
                    const cursor = e.target.result;
                    if (!cursor) { resolve(); return; }
                    if (cursor.value && cursor.value.owner === LazyGridUtils.PAGE_ID) cursor.delete();
                    cursor.continue();
                };
                req.onerror = () => resolve();
            })
        ).catch(e => console.warn('[IDB] clearModule error:', e));
    }
}

// ══════════════════════════════════════════════════════════════════════════════
// LazyGridUtils — pure / stateless helpers
// ══════════════════════════════════════════════════════════════════════════════
class LazyGridUtils {
    /** Identitas instance halaman ini; berganti setiap page load / tab baru. */
    static PAGE_ID = Date.now().toString(36) + Math.random().toString(36).slice(2, 10);

    /**
     * PAGE_ID halaman sebelumnya di tab yang sama. Halaman itu pasti sudah ditutup,
     * jadi entrinya dibuang saat DB dibuka (purgeExpired). Ini jalur pembersihan
     * yang andal; pagehide sering terpotong browser sebelum transaksi selesai.
     */
    static PREV_PAGE_ID = (function () {
        try {
            const k = 'sysmodern_lazygrid_page';
            const prev = sessionStorage.getItem(k);
            sessionStorage.setItem(k, LazyGridUtils.PAGE_ID);
            return prev;
        } catch (e) { return null; }
    })();

    /**
     * Kunci cakupan data: instance halaman + seluruh parameter yang menentukan isi
     * halaman (postData dari filter card, filter toolbar, sort, jumlah baris).
     * Dulu hanya hash `filters`, sehingga cabang/periode/sort yang berbeda -- mis.
     * di tab lain -- berbagi kunci yang sama dan datanya bisa tertukar.
     * Sengaja string utuh, bukan hash, supaya tabrakan mustahil.
     */
    static scopeKey(grid, rowsCount) {
        const pd = $.extend({}, grid.jqGrid('getGridParam', 'postData'));
        ['page', 'rows', 'nd', '_search', 'sidx', 'sord'].forEach(k => delete pd[k]);
        pd.__sidx = grid.jqGrid('getGridParam', 'sortname');
        pd.__sord = grid.jqGrid('getGridParam', 'sortorder');
        pd.__rows = rowsCount;
        const norm = Object.keys(pd).sort().map(k => {
            let v = pd[k];
            if (typeof v === 'function') v = v();
            return k + '=' + (v !== null && typeof v === 'object' ? JSON.stringify(v) : String(v));
        }).join('&');
        return LazyGridUtils.PAGE_ID + '|' + norm;
    }

    static buildFilterKey(filters) {
        const str = (filters == null) ? '' : String(filters);
        let hash = 5381;
        for (let i = 0; i < str.length; i++) {
            hash = ((hash << 5) + hash) + str.charCodeAt(i);
            hash = hash & hash;
        }
        return (hash >>> 0).toString(16);
    }

    static buildCacheKey(moduleName, pageNumber, filterKey) {
        return moduleName + '_p' + pageNumber + '_f' + filterKey;
    }

    static getModuleNameFromApi(apiUrl) {
        if (!apiUrl) return 'unknown_module';
        const path = apiUrl.split('?')[0].replace(/^https?:\/\/[^\/]+/, '');
        return path.replace(/[^a-zA-Z0-9]/g, '_').replace(/^_+|_+$/g, '');
    }
}

// Entri IndexedDB hanya berguna selama halaman ini terbuka (kuncinya memuat
// PAGE_ID). Saat halaman ditinggalkan -- pindah menu, tutup tab, logout -- milik
// instance ini dihapus. Best effort: bila browser memotongnya, purgeExpired saat
// DB dibuka berikutnya yang membereskan.
window.addEventListener('pagehide', function () {
    if (LazyGridIDB._db) LazyGridIDB.clearModule(null);
});

var lazyStates = {};

function getGridState(grid) {
    var id = grid;
    if (typeof grid === 'object') id = grid.attr('id') || grid.jqGrid('getGridParam', 'id');
    if (typeof id === 'string') id = id.replace('#', '');
    if (!lazyStates[id]) {
        lazyStates[id] = {
            totalPages: 1,
            totalRecord: 0,
            loading: false,
            loadingQueue: [],
            cachedData: {},
            minPageLoaded: 1,
            maxPageLoaded: 1,
            lastScrollTop: 0,
            currentFilters: null,
            currentSortIdx: null,
            currentSortOrd: null,
            currentViewPage: 1,
            lsPrefetchingPages: new Set()
        };
    }
    return lazyStates[id];
}

function triggerPrefetch(gridId, api, postData, fromPage, rowsCount) {
    var grid = $(gridId);
    var state = getGridState(grid);
    var filterKey = LazyGridUtils.scopeKey(grid, rowsCount);
    var moduleName = LazyGridUtils.getModuleNameFromApi(api);
    var prefetchAhead = 3;

    if (!state.lsPrefetchingPages) state.lsPrefetchingPages = new Set();

    for (let ahead = 1; ahead <= prefetchAhead; ahead++) {
        let target = fromPage + ahead;
        if (state.totalPages && target > state.totalPages) break;
        if (state.cachedData[target]) continue;
        if (state.lsPrefetchingPages.has(target)) continue;

        ((pg, delay) => {
            LazyGridIDB.loadPage(moduleName, pg, filterKey, LazyGridIDB.MAX_AGE_MS).then(function(idbData) {
                if (idbData && idbData.length > 0) return;
                if (state.cachedData[pg] || state.lsPrefetchingPages.has(pg)) return;
                
                state.lsPrefetchingPages.add(pg);
                setTimeout(function() {
                    loadGridData(gridId, api, postData, pg, rowsCount, 'down', 'page', null, true);
                }, delay);
            });
        })(target, (ahead - 1) * 300);
    }
}

var WINDOW_PAGES = 3;
var rowsPerPage = 50;
var gapPage = 15;

/**
 * Batas halaman yang dipegang di memori per grid. Halaman di luar jendela DOM
 * (minPageLoaded..maxPageLoaded) yang paling jauh dibuang dari memori; salinannya
 * tetap ada di IndexedDB untuk dibaca ulang tanpa ke server.
 */
var MAX_MEMORY_PAGES = 10;

function pruneMemoryCache(state) {
    var pages = Object.keys(state.cachedData).map(Number);
    if (pages.length <= MAX_MEMORY_PAGES) return;

    var lo = state.minPageLoaded, hi = state.maxPageLoaded;
    var dist = function (p) { return p < lo ? lo - p : (p > hi ? p - hi : 0); };
    pages
        .filter(function (p) { return dist(p) > 0; })   // halaman yang sedang tampil tidak dibuang
        .sort(function (a, b) { return dist(b) - dist(a); })
        .slice(0, pages.length - MAX_MEMORY_PAGES)
        .forEach(function (p) { delete state.cachedData[p]; });
}

function throttle(func, limit) {
    let inThrottle;
    let lastArgs;
    return function () {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => {
                inThrottle = false;
                if (lastArgs) {
                    func.apply(context, lastArgs);
                    lastArgs = null;
                }
            }, limit);
        } else {
            lastArgs = args;
        }
    };
}

function hasFilterChanged(grid) {
    var state = getGridState(grid);
    var rawNewFilters = grid.jqGrid('getGridParam', 'postData').filters;
    var newFilters = (rawNewFilters === undefined || rawNewFilters === null) ? '' : rawNewFilters;
    var oldFilters = (state.currentFilters === undefined || state.currentFilters === null) ? '' : state.currentFilters;
    let newSort = grid.jqGrid('getGridParam', 'sortname');
    let newOrder = grid.jqGrid('getGridParam', 'sortorder');

    if (state.currentFilters === null || state.currentFilters === undefined || state.currentSortIdx === null || state.currentSortIdx === undefined) {
        state.currentFilters = newFilters;
        state.currentSortIdx = newSort;
        state.currentSortOrd = newOrder;
        return false;
    }

    var isChanged = (oldFilters !== newFilters) || (state.currentSortIdx !== newSort) || (state.currentSortOrd !== newOrder);
    if (isChanged) {
        state.currentFilters = newFilters;
        state.currentSortIdx = newSort;
        state.currentSortOrd = newOrder;
        return true;
    }
    return false;
}

function resetGridState(grid, resetFilters = false) {
    var state = getGridState(grid);
    state.cachedData = {};
    state.minPageLoaded = 1;
    state.maxPageLoaded = 1;
    state.lastScrollTop = 0;
    state.loading = false;
    state.jumping = false;
    // Jump PageUp/PageDown yang masih ditunda (mains.js) tidak relevan lagi.
    clearTimeout(state.jumpTimer);
    state.jumpTimer = null;
    state.pendingTargetAbs = undefined;
    state.loadingQueue = [];
    if (resetFilters) state.currentFilters = null;
    
    var api = grid.jqGrid('getGridParam', 'url');
    if (api) {
        var moduleName = LazyGridUtils.getModuleNameFromApi(api);
        LazyGridIDB.clearModule(moduleName);
    }
    
    grid.jqGrid('clearGridData');
}

function processLoadingQueue(gridId, api, postData, rowsCount) {
    var grid = $(gridId);
    var state = getGridState(grid);
    if (state.loadingQueue.length && !state.loading) {
        var next = state.loadingQueue.shift();
        loadGridData(gridId, api, postData, next.page, rowsCount, next.direction, 'page', next.callback);
    }
}

/**
 * Dipanggil tombol Reset di tiap view, setelah clearToolbar().
 * - Menyembunyikan tombol silang filter yang inputnya sudah kosong.
 * - Mengembalikan urutan sort ke urutan saat grid pertama kali dimuat,
 *   beserta panah di header.
 * Footer tidak disentuh: loadComplete menggambarnya ulang setelah data dimuat.
 */
function resetLazyGridView(gridSelector) {
    var grid = $(gridSelector);
    if (!grid.length || !grid[0].grid) return;

    var el = grid[0];
    var view = grid.closest('.ui-jqgrid-view');
    var disabledClass = 'ui-state-disabled ui-disabled';

    view.find('.ui-search-toolbar .clearsearchclass').attr('style', 'display: none !important');

    var state = getGridState(grid);
    if (state.defaultSortName === undefined) return;

    var colModel = el.p.colModel || [];
    var sortCol = -1;
    for (var i = 0; i < colModel.length; i++) {
        if (state.defaultSortName !== '' && (colModel[i].name === state.defaultSortName || colModel[i].index === state.defaultSortName)) {
            sortCol = i;
            break;
        }
    }

    // Matikan panah di semua kolom, lalu nyalakan hanya di kolom urutan awal.
    for (var h = 0; h < el.grid.headers.length; h++) {
        var th = el.grid.headers[h].el;
        $('span.s-ico', th).hide();
        $('span.ui-icon-asc, span.ui-icon-desc', th).addClass(disabledClass);
    }
    if (sortCol >= 0) {
        var sortTh = el.grid.headers[sortCol].el;
        $('span.s-ico', sortTh).show();
        $('span.ui-icon-' + state.defaultSortOrd, sortTh).removeClass(disabledClass);
    }

    el.p.sortname = state.defaultSortName;
    el.p.sortorder = state.defaultSortOrd;
    el.p.lastsort = sortCol >= 0 ? sortCol : 0;
    if (el.p.postData) {
        el.p.postData.sidx = state.defaultSortName;
        el.p.postData.sord = state.defaultSortOrd;
    }
}

function loadGridData(gridId, api, postData, pageNumber, rowsCount, direction = 'down', proses = 'page', callback, onlyCache = false) {
    var grid = $(gridId);
    var state = getGridState(grid);
    rowsPerPage = rowsCount;

    // Urutan saat muatan pertama dicatat sebagai urutan awal untuk tombol Reset.
    if (state.defaultSortName === undefined) {
        state.defaultSortName = grid.jqGrid('getGridParam', 'sortname') || '';
        state.defaultSortOrd = grid.jqGrid('getGridParam', 'sortorder') || 'asc';
    }

    if (proses !== 'reload' && proses !== 'jump' && state.minPageLoaded > 0 && hasFilterChanged(grid)) {
        resetGridState(grid);
        pageNumber = 1;
        direction = 'down';
        proses = 'reload';
    }

    // Tiap muatan baru (reload/jump) menaikkan nomor generasi. Respons dari generasi
    // lama (request lambat yang baru tiba) diabaikan supaya tidak menimpa data terbaru.
    if (proses === 'reload' || proses === 'jump') {
        state.loadSeq = (state.loadSeq || 0) + 1;
        if (state.lsPrefetchingPages) state.lsPrefetchingPages.clear();
    }

    if (proses === 'reload') {
        resetGridState(grid);
        state.minPageLoaded = pageNumber;
        state.maxPageLoaded = pageNumber;
        state.currentFilters = grid.jqGrid('getGridParam', 'postData').filters;
        state.currentSortIdx = grid.jqGrid('getGridParam', 'sortname');
        state.currentSortOrd = grid.jqGrid('getGridParam', 'sortorder');
        state.loading = false;
    }

    // Nomor generasi milik panggilan ini. Render yang tiba setelah ada reload/jump
    // baru (mis. hasil IndexedDB yang lambat) dibuang supaya tidak menimpa grid.
    var callSeq = state.loadSeq || 0;

    if (proses === 'jump') {
        // Selama jump berjalan grid sempat kosong dan scrollTop kembali ke 0.
        // Tanpa penanda ini scroll handler mengira user scroll ke atas lalu memuat
        // halaman sebelumnya sampai halaman 1, dan selection kembali ke baris pertama.
        state.jumping = true;
        state.loading = true;
        if (state.jumpXhr && state.jumpXhr.readyState !== 4) state.jumpXhr.abort();
        grid.jqGrid('clearGridData');
        state.minPageLoaded = pageNumber;
        state.maxPageLoaded = pageNumber;
        state.lastScrollTop = 0;
        state.loading = false;
    }

    // Jump yang dilayani dari cache selesai tanpa ajax, jadi penanda jumping
    // dilepas di sini. Ditunda sedikit supaya event scroll akibat clearGridData
    // masih diabaikan scroll handler; dilewati bila sudah ada jump yang lebih baru.
    function finishCachedJump() {
        if (proses !== 'jump') return;
        setTimeout(function () {
            if (callSeq === (state.loadSeq || 0)) state.jumping = false;
        }, 50);
    }

    if (state.cachedData[pageNumber] && (proses === 'page' || proses === 'jump')) {
        renderFromCache(grid, state.cachedData[pageNumber], direction, rowsCount, pageNumber);
        if (proses === 'jump') triggerPrefetch(gridId, api, postData, pageNumber, rowsCount);
        finishCachedJump();
        if (callback) callback();
        return;
    }

    var filterKey = LazyGridUtils.scopeKey(grid, rowsCount);
    var moduleName = LazyGridUtils.getModuleNameFromApi(api);

    if (proses === 'page' || proses === 'jump') {
        LazyGridIDB.loadPage(moduleName, pageNumber, filterKey, LazyGridIDB.MAX_AGE_MS).then(function(idbData) {
            if (callSeq !== (state.loadSeq || 0)) return; // sudah ada reload/jump baru

            if (idbData && idbData.length > 0) {
                state.cachedData[pageNumber] = idbData;
                if (state.lsPrefetchingPages) state.lsPrefetchingPages.delete(pageNumber);

                if (!onlyCache) {
                    renderFromCache(grid, idbData, direction, rowsCount, pageNumber);
                    triggerPrefetch(gridId, api, postData, pageNumber, rowsCount);
                }
                pruneMemoryCache(state);
                finishCachedJump();
                if (callback) callback();
            } else {
                fetchFromServer();
            }
        });
    } else {
        fetchFromServer();
    }

    function fetchFromServer() {
        if (!onlyCache) {
            if (state.loading) {
                var alreadyQueued = state.loadingQueue.some(function (q) { return q.page === pageNumber; });
                // callback ikut disimpan: tanpa itu panah atas/bawah yang sempat
                // antre tidak pernah memilih baris tujuannya.
                if (!alreadyQueued) state.loadingQueue.push({ page: pageNumber, direction: direction, callback: callback });
                return;
            }
            state.loading = true;
            $('.loaderGrid').removeClass('d-none');
        }

        var requestSeq = state.loadSeq || 0;
        var currentFilters = grid.jqGrid('getGridParam', 'postData').filters;
        var _isSearch = grid.jqGrid('getGridParam', 'postData')._search;
        if (currentFilters) {
            try {
                var filtersObj = JSON.parse(currentFilters);
                _isSearch = (filtersObj.rules && filtersObj.rules.length > 0);
            } catch(e) {}
        }

        var fullPostData = $.extend({}, postData, {
            page: pageNumber,
            rows: rowsCount,
            sidx: grid.jqGrid('getGridParam', 'sortname'),
            sord: grid.jqGrid('getGridParam', 'sortorder'),
            filters: currentFilters,
            _search: _isSearch
        });

        // Hanya hasil jump terakhir yang berguna: batalkan jump sebelumnya yang
        // masih berjalan. (Query di server tetap selesai; yang dibatalkan hanya
        // penantian di browser. Debounce di mains.js yang menekan jumlahnya.)
        if (proses === 'jump' && state.jumpXhr && state.jumpXhr.readyState !== 4) {
            state.jumpXhr.abort();
        }

        var xhr = $.ajax({
            url: api,
            type: 'POST',
            data: fullPostData,
            success: function (res) {
                if (requestSeq !== (state.loadSeq || 0)) return; // respons basi
                if (!onlyCache) $('.loaderGrid').addClass('d-none');
                state.totalRecord = res.records;
                state.totalPages = res.total;

                if (!res.rows || !res.rows.length) {
                    if (!onlyCache) state.loading = false;
                    if (state.lsPrefetchingPages) state.lsPrefetchingPages.delete(pageNumber);

                    // Hasil kosong pada muatan baru (reload/jump): beri tahu grid supaya
                    // footer, userdata dan info tidak menampilkan angka dari data sebelumnya.
                    if (!onlyCache && proses !== 'page') {
                        grid.jqGrid('setGridParam', { userData: res.userdata || {}, records: 0 });
                        var emptyLoadComplete = grid.jqGrid('getGridParam', 'loadComplete');
                        if (typeof emptyLoadComplete === 'function') emptyLoadComplete.call(grid[0], res);
                        var emptyGridId = grid.attr('id') || grid.jqGrid('getGridParam', 'id');
                        $('#' + emptyGridId + 'InfoHandler').text('View 0 - 0 of 0');
                    }
                    return;
                }

                var mappedRows = res.rows;
                if (res.rows[0].cell && Array.isArray(res.rows[0].cell)) {
                    var cm = grid.jqGrid('getGridParam', 'colModel');
                    mappedRows = res.rows.map(function (row) {
                        var newRow = { id: row.id };
                        var cellIdx = 0;
                        for (var j = 0; j < cm.length; j++) {
                            if (cm[j].name !== 'rn' && cm[j].name !== 'cb' && cm[j].name !== 'subgrid') {
                                newRow[cm[j].name] = row.cell[cellIdx++];
                            }
                        }
                        return newRow;
                    });
                }

                state.cachedData[pageNumber] = mappedRows;
                LazyGridIDB.savePage(moduleName, pageNumber, mappedRows, filterKey);
                if (state.lsPrefetchingPages) state.lsPrefetchingPages.delete(pageNumber);

                if (onlyCache) {
                    pruneMemoryCache(state);
                    return; // Prefetch selesai, jangan render
                }

                if (res.userdata) {
                    grid.jqGrid('setGridParam', { userData: res.userdata });
                }

                renderFromCache(grid, mappedRows, direction, rowsCount, pageNumber, res);
                grid.jqGrid('setGridParam', { records: state.totalRecord });
                
                triggerPrefetch(gridId, api, fullPostData, pageNumber, rowsCount);
                pruneMemoryCache(state);
                if (callback) callback(res);
            },
            error: function (xhr, textStatus, errorThrown) {
                if (requestSeq !== (state.loadSeq || 0) || textStatus === 'abort') return;
                if (!onlyCache) $('.loaderGrid').addClass('d-none');
                console.error('Grid Load Error:', textStatus, errorThrown);
                if (state.lsPrefetchingPages) state.lsPrefetchingPages.delete(pageNumber);
            },
            complete: function () {
                if (requestSeq !== (state.loadSeq || 0)) return;
                if (proses === 'jump') state.jumping = false;
                if (!onlyCache) {
                    state.loading = false;
                    var freshPostData = grid.jqGrid('getGridParam', 'postData');
                    processLoadingQueue(gridId, api, freshPostData, rowsCount);
                }
            }
        });

        if (proses === 'jump') state.jumpXhr = xhr;
    }
}

function setupLazyLoadScrollHandler(gridId, apiUrl, postData) {
    var grid = $(gridId);
    var state = getGridState(grid);

    var throttledScroll = throttle(function () {
        var bDiv = $(this);
        var scrollTop = bDiv.scrollTop();

        // Scroll yang terjadi karena grid dikosongkan saat jump bukan gerakan user.
        if (state.jumping) {
            state.lastScrollTop = scrollTop;
            return;
        }

        var viewHeight = bDiv.height();
        var tableHeight = bDiv.find('table').height();
        var rowNum = grid.jqGrid('getGridParam', 'rowNum');
        var rowHeight = grid.find('tr[id]').height() || 30;
        var currentPostData = grid.jqGrid('getGridParam', 'postData');
        var detectedPage = detectCurrentViewPage(grid);

        if (detectedPage !== state.currentViewPage) {
            state.currentViewPage = detectedPage;
            updateGridInfoFast(grid);
        }

        if (scrollTop > state.lastScrollTop && scrollTop + viewHeight >= tableHeight - (gapPage * rowHeight)) {
            state.lastScrollTop = scrollTop;
            var nextPage = state.maxPageLoaded + 1;
            if (nextPage > state.totalPages) return;

            if (state.cachedData[nextPage]) {
                renderFromCache(grid, state.cachedData[nextPage], 'down', rowNum, nextPage);
            } else {
                loadGridData(gridId, apiUrl, currentPostData, nextPage, rowNum, 'down', 'page');
            }
            return;
        }

        var triggerThreshold = gapPage * rowHeight;
        if (state.maxPageLoaded === state.minPageLoaded) triggerThreshold = tableHeight * 0.8;

        if (scrollTop < state.lastScrollTop && scrollTop <= triggerThreshold && state.minPageLoaded > 1) {
            var prevPage = state.minPageLoaded - 1;
            if (state.cachedData[prevPage]) {
                renderFromCache(grid, state.cachedData[prevPage], 'up', rowNum, prevPage);
            } else {
                loadGridData(gridId, apiUrl, currentPostData, prevPage, rowNum, 'up', 'page');
                state.lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
            }
            return;
        }

        state.lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
    }, 150);

    grid.parents('.ui-jqgrid-bdiv').off('scroll.virtual').on('scroll.virtual', throttledScroll);
}

function renderFromCache(grid, data, direction, rowsPerPage, currentPage, res) {
    var state = getGridState(grid);
    var selectedId = grid.jqGrid('getGridParam', 'selrow');
    var existingIds = grid.jqGrid('getDataIDs');
    var existingIdSet = new Set(existingIds.map(String));

    var originalOnSelectRow = grid.jqGrid('getGridParam', 'onSelectRow');
    var originalOnSelectAll = grid.jqGrid('getGridParam', 'onSelectAll');
    grid.jqGrid('setGridParam', { onSelectRow: null, onSelectAll: null });

    if (direction === 'down') {
        data.forEach(function (row) {
            if (!existingIdSet.has(row.id.toString())) {
                grid.jqGrid('addRowData', row.id, row, 'last');
            } else {
                grid.jqGrid('setRowData', row.id, row);
            }
        });
        let validPage = currentPage || state.maxPageLoaded;
        state.maxPageLoaded = Math.max(state.maxPageLoaded, validPage);
        trimGridRows(grid, 'down', rowsPerPage);
    } else if (direction === 'up') {
        var scrollDiv = grid.parents('.ui-jqgrid-bdiv');
        var prevScroll = scrollDiv.scrollTop();
        var addedCount = 0;

        for (var i = data.length - 1; i >= 0; i--) {
            if (!existingIdSet.has(data[i].id.toString())) {
                grid.jqGrid('addRowData', data[i].id, data[i], 'first');
                addedCount++;
            }
        }

        let validPage = currentPage || state.minPageLoaded;
        state.minPageLoaded = Math.min(state.minPageLoaded, validPage);
        state.currentViewPage = currentPage;
        trimGridRows(grid, 'up', rowsPerPage);

        if (addedCount > 0) {
            var rowHeight = grid.find('tr[id]').height() || 30;
            var newScrollTop = prevScroll + (addedCount * rowHeight);
            scrollDiv.scrollTop(newScrollTop);
            state.lastScrollTop = newScrollTop;
        }
        trimGridRows(grid, 'up', rowsPerPage);
    } else if (direction === 'jump' || direction === 'reload') {
        grid.jqGrid('clearGridData');
        data.forEach(function (row) { grid.jqGrid('addRowData', row.id, row, 'last'); });
        state.currentViewPage = parseInt(currentPage) || 1;
        state.minPageLoaded = state.currentViewPage;
        state.maxPageLoaded = state.currentViewPage;
        grid.jqGrid('setGridParam', { page: state.currentViewPage, records: state.totalRecord });
        selectedId = null;
    }

    grid.jqGrid('setGridParam', { onSelectRow: originalOnSelectRow, onSelectAll: originalOnSelectAll });
    pruneMemoryCache(state);

    if (typeof setHighlight === 'function') setHighlight(grid);
    refreshRowNumbers(grid, state.minPageLoaded, rowsPerPage);
    updateGridInfoFast(grid);

    var loadCompleteFunc = grid.jqGrid('getGridParam', 'loadComplete');
    if (typeof loadCompleteFunc === 'function') {
        var userData = grid.jqGrid('getGridParam', 'userData');
        loadCompleteFunc.call(grid[0], res || { userdata: userData });
    }
}

function trimGridRows(grid, direction, rowsPerPage) {
    var state = getGridState(grid);
    var ids = grid.jqGrid('getDataIDs');
    var maxRows = WINDOW_PAGES * rowsPerPage;
    if (ids.length <= maxRows) return;

    var excess = ids.length - maxRows;
    var pagesRemoved = Math.max(1, Math.floor(excess / rowsPerPage));

    if (direction === 'down') {
        for (let i = 0; i < excess; i++) grid.jqGrid('delRowData', ids[i]);
        state.minPageLoaded += pagesRemoved;
    } else if (direction === 'up') {
        for (let i = ids.length - 1; i >= ids.length - excess; i--) grid.jqGrid('delRowData', ids[i]);
        state.maxPageLoaded -= pagesRemoved;
    }
    refreshRowNumbers(grid, state.minPageLoaded, rowsPerPage);
}

function refreshRowNumbers(grid, pageNumber, rowLimit) {
    var state = getGridState(grid);
    var ids = grid.jqGrid('getDataIDs');
    if (ids.length === 0) return;
    var limit = parseInt(rowLimit) || 50;
    var idToNumber = {};
    for (var pg in state.cachedData) {
        var realPage = parseInt(pg);
        state.cachedData[pg].forEach(function (row, idx) {
            idToNumber[row.id.toString()] = (realPage - 1) * limit + idx + 1;
        });
    }
    ids.forEach(function (currentId, i) {
        var num = idToNumber[currentId] || ((state.minPageLoaded - 1) * limit + i + 1);
        if (state.totalRecord > 0 && num > state.totalRecord) num = state.totalRecord;
        grid.jqGrid('setCell', currentId, 'rn', num);
    });
    updateGridInfoFast(grid);
}

function updateGridInfoFast(grid) {
    var state = getGridState(grid);
    var start = (state.currentViewPage - 1) * rowsPerPage + 1;
    var actual = state.cachedData[state.currentViewPage] ? state.cachedData[state.currentViewPage].length : rowsPerPage;
    var end = start + actual - 1;
    if (state.totalRecord > 0 && end > state.totalRecord) end = state.totalRecord;
    var gridIdStr = grid.attr('id') || grid.getGridParam('id');
    $('#' + gridIdStr + 'InfoHandler').text('View ' + start + ' - ' + end + ' of ' + state.totalRecord);
}

function detectCurrentViewPage(grid) {
    var state = getGridState(grid);
    var scrollDiv = grid.parents('.ui-jqgrid-bdiv');
    var scrollTop = scrollDiv.scrollTop();
    var viewHeight = scrollDiv.height();
    var rowHeight = grid.find('tr[id]').height() || 30;
    var visibleIndex = Math.floor((scrollTop + (viewHeight / 2)) / rowHeight);
    var recordNumber = (state.minPageLoaded - 1) * rowsPerPage + visibleIndex + 1;
    var page = Math.ceil(recordNumber / rowsPerPage);
    return Math.max(1, Math.min(page, state.totalPages));
}
