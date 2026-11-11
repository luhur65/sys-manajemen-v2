/**
 * GridPreferenceManager
 * ─────────────────────────────────────────────────────────────────────────────
 * Satu-satunya pemilik format & penyimpanan preferensi kolom grid.
 *
 * Tiga aturan yang dipegang file ini:
 *
 *  1. SATU SERIALIZER. Baik autosave setelah resize maupun tombol "Terapkan" di
 *     panel sama-sama lewat serialize() yang membaca colModel hidup. Sebelumnya
 *     ada dua jalur dengan bentuk berbeda -- jalur resize ikut menyimpan kolom
 *     sistem jqGrid ('rn') dan menghitung order dari colModel penuh, jalur panel
 *     membuangnya dan menghitung ulang order dari 0 -- sehingga satu file bisa
 *     berisi dua format yang saling bertabrakan.
 *
 *  2. REKONSILIASI SAAT BACA. Preferensi tersimpan tidak pernah dipercaya apa
 *     adanya. reconcile() mencocokkannya dengan colModel yang berlaku sekarang:
 *     kolom yang sudah tidak ada dibuang, kolom baru disisipkan di posisi
 *     alaminya. Tanpa ini, satu kali rename kolom oleh developer cukup untuk
 *     merusak grid semua user yang sudah menyimpan preferensi.
 *
 *  3. AMPLOP BERVERSI. Yang ditulis selalu { v, updatedAt, columns }. Pembaca
 *     tetap menerima array telanjang (format v1) supaya data lama terbaca.
 *
 * Kenapa $.ajax biasa, bukan ajaxWithRefresh:
 *   $.ajaxPrefilter sudah intercept 401 dan handle refresh untuk SEMUA $.ajax.
 *   ajaxWithRefresh juga intercept 401 di .catch dan handle refresh lagi, jadi
 *   satu request 401 ditangani DUA kali. ajaxWithRefresh hanya perlu dipakai
 *   kalau butuh slow threshold warning.
 */

const GridPreferenceManager = (function () {

  const STORAGE_VERSION = 2;

  // Kolom yang disuntikkan jqGrid sendiri saat init ('rn' dari rownumbers, 'cb'
  // dari multiselect, 'subgrid'). Tidak pernah ada di colModel yang ditulis
  // developer, jadi tidak boleh ikut disimpan.
  const SYSTEM_COLS = ['rn', 'cb', 'subgrid'];

  const CONFIG = {
    localPrefix: 'grid_pref_',
    serverUrlLoad: `${typeof appUrl !== 'undefined' ? appUrl.replace(/\/$/, '') : ''}/gridpreference/load`,
    serverUrlSave: `${typeof appUrl !== 'undefined' ? appUrl.replace(/\/$/, '') : ''}/gridpreference/save`,
    serverUrlDelete: `${typeof appUrl !== 'undefined' ? appUrl.replace(/\/$/, '') : ''}/gridpreference/delete`,
    debounceMs: 800,
    mode: 'server', // Aktifkan sinkronisasi server via file JSON
  };

  function debounce(fn, delay) {
    let timer;
    return function (...args) {
      clearTimeout(timer);
      timer = setTimeout(() => fn.apply(this, args), delay);
    };
  }

  function isSystemCol(name) {
    return SYSTEM_COLS.includes(String(name || '').toLowerCase());
  }

  /** jqGrid menormalkan width ke angka saat init, tapi colModel mentah bisa
   *  berisi string seperti "100px". Kembalikan undefined kalau tidak masuk akal
   *  supaya pemanggil bisa jatuh ke width bawaan kolom. */
  function normalizeWidth(w) {
    const n = parseInt(w, 10);
    return Number.isFinite(n) && n > 0 ? n : undefined;
  }

  // ─── Amplop versi ─────────────────────────────────────────────

  function wrap(columns) {
    return { v: STORAGE_VERSION, updatedAt: Date.now(), columns: columns };
  }

  /** Terima amplop v2 maupun array telanjang v1. Selalu kembalikan array kolom
   *  atau null bila tidak ada isinya. */
  function unwrap(raw) {
    if (!raw) return null;
    if (Array.isArray(raw)) return raw.length ? raw : null;
    if (Array.isArray(raw.columns)) return raw.columns.length ? raw.columns : null;
    return null;
  }

  // ─── localStorage ─────────────────────────────────────────────

  function currentUserId() {
    // Ambil userid dari localStorage lockscreen. Key diberi prefix 'sysmodern_'
    // -- HARUS SAMA dengan APP_NS di lockscreen.js -- karena localStorage
    // di-scope per-origin browser, bukan per-folder/path, dan proyek CI4 lain
    // (mis. emkl-approval-sby-ci4) bisa saja diakses dari origin yang sama.
    return localStorage.getItem('sysmodern_lockscreen_userid') || 'guest';
  }

  function localKey(gridName) {
    return CONFIG.localPrefix + currentUserId() + '_' + gridName;
  }

  function saveLocal(gridName, prefs) {
    try {
      localStorage.setItem(localKey(gridName), JSON.stringify(wrap(prefs)));
    } catch (e) {
      console.warn('[GridPref] Gagal simpan localStorage:', e);
    }
  }

  function loadLocal(gridName) {
    try {
      const raw = localStorage.getItem(localKey(gridName));
      if (raw) return unwrap(JSON.parse(raw));
      return migrateLegacyLocal(gridName);
    } catch (e) {
      console.warn('[GridPref] Gagal baca localStorage:', e);
      return null;
    }
  }

  function clearLocal(gridName) {
    localStorage.removeItem(localKey(gridName));
    legacyLocalKeys(gridName).forEach(k => localStorage.removeItem(k));
  }

  // ─── Migrasi kunci lama ───────────────────────────────────────
  //
  // Kunci lama dibentuk dari window.location.pathname: setiap karakter non
  // alfanumerik diganti '_', lalu ditambah '_' + gridId. Contoh nyata:
  //   "_sys_modern_piutangemkl_index_jqGrid"
  // Kunci baru dibentuk server-side dari router: "piutangemkl.index.jqGrid".
  //
  // Sebuah kunci lama dianggap milik kunci baru bila ia berakhiran '_<gridId>'
  // dan sisa segmennya (huruf kecil) berakhiran [controller, method] -- atau
  // berakhiran [controller] saja ketika method-nya 'index', karena
  // '/piutangemkl' dan '/piutangemkl/index' adalah halaman yang sama.
  //
  // Aturan yang sama diterapkan di sisi server, lihat
  // App\Controllers\GridPreference::legacyKeysFor().

  function scopeParts(gridName) {
    const parts = String(gridName || '').split('.');
    if (parts.length < 3) return null;
    const gridId = parts[parts.length - 1];
    const method = parts[parts.length - 2].toLowerCase();
    const controller = parts[parts.length - 3].toLowerCase();
    if (!gridId || !method || !controller) return null;
    return { gridId, method, controller };
  }

  function legacyTailMatches(tail, scope) {
    const segs = tail.split('_').filter(s => s.length).map(s => s.toLowerCase());
    if (segs.length >= 2 &&
        segs[segs.length - 2] === scope.controller &&
        segs[segs.length - 1] === scope.method) {
      return true;
    }
    return scope.method === 'index' && segs.length >= 1 && segs[segs.length - 1] === scope.controller;
  }

  /** Daftar kunci localStorage lama yang merujuk grid yang sama. */
  function legacyLocalKeys(gridName) {
    const scope = scopeParts(gridName);
    if (!scope) return [];
    const prefix = CONFIG.localPrefix + currentUserId() + '_';
    const suffix = '_' + scope.gridId;
    const found = [];
    for (let i = 0; i < localStorage.length; i++) {
      const key = localStorage.key(i);
      if (!key || !key.startsWith(prefix) || key === prefix + gridName) continue;
      const body = key.slice(prefix.length);
      if (!body.endsWith(suffix)) continue;
      if (legacyTailMatches(body.slice(0, -suffix.length), scope)) found.push(key);
    }
    return found;
  }

  /** Pindahkan preferensi dari kunci lama ke kunci baru, sekali jalan. */
  function migrateLegacyLocal(gridName) {
    const legacy = legacyLocalKeys(gridName);
    if (!legacy.length) return null;

    let columns = null;
    for (const key of legacy) {
      try {
        const cols = unwrap(JSON.parse(localStorage.getItem(key)));
        // Ambil kandidat terkaya bila ada beberapa ejaan URL untuk grid yang sama.
        if (cols && (!columns || cols.length > columns.length)) columns = cols;
      } catch (e) { /* kunci rusak, abaikan */ }
    }

    legacy.forEach(k => localStorage.removeItem(k));
    if (!columns) return null;

    console.info('[GridPref] Migrasi kunci lama ke "' + gridName + '" (' + legacy.length + ' kunci).');
    saveLocal(gridName, columns);
    return columns;
  }

  // ─── Server — pakai $.ajax biasa ─────────────────────────────
  // $.ajaxSetup → Authorization + X-CSRF-TOKEN otomatis disertakan
  // $.ajaxPrefilter → 401 → refresh token → retry  (sudah cukup)

  function saveServer(gridName, prefs) {
    return $.ajax({
      url: CONFIG.serverUrlSave,
      type: 'POST',
      contentType: 'application/json',
      dataType: 'json',
      data: JSON.stringify({ grid_name: gridName, preferences: wrap(prefs) }),
    }).fail(function (jqXHR) {
      if (jqXHR.status !== 401) {
        console.warn('[GridPref] Gagal simpan ke server:', jqXHR.status, jqXHR.statusText);
      }
    });
  }

  function loadServer(gridName) {
    return $.ajax({
      url: `${CONFIG.serverUrlLoad}?grid_name=${encodeURIComponent(gridName)}`,
      type: 'GET',
      dataType: 'json',
    })
      .then(function (res) {
        return unwrap(res && res.preferences);
      })
      .catch(function (jqXHR) {
        if (jqXHR.status !== 401) {
          console.warn('[GridPref] Gagal baca dari server:', jqXHR.status);
        }
        return null;
      });
  }

  function deleteServer(gridName) {
    return $.ajax({
      url: CONFIG.serverUrlDelete,
      type: 'POST',
      contentType: 'application/json',
      dataType: 'json',
      data: JSON.stringify({ grid_name: gridName }),
    }).fail(function (jqXHR) {
      if (jqXHR.status !== 401) {
        console.warn('[GridPref] Gagal delete dari server:', jqXHR.status, jqXHR.statusText);
      }
    });
  }

  // ─── Serializer tunggal ───────────────────────────────────────

  /**
   * Bentuk kanonik preferensi dari colModel yang sedang hidup di grid.
   * Dipakai jalur autosave-setelah-resize DAN tombol "Terapkan" di panel, jadi
   * keduanya mustahil menghasilkan bentuk yang berbeda.
   */
  function serialize(gridSelector) {
    const colModel = $(gridSelector).jqGrid('getGridParam', 'colModel') || [];
    return colModel
      .filter(col => col && col.name && !isSystemCol(col.name))
      .map((col, index) => ({
        name: col.name,
        width: normalizeWidth(col.width),
        hidden: !!col.hidden,
        frozen: !!col.frozen,
        order: index,
      }));
  }

  /** Serialize + simpan (local, lalu server bila mode server). */
  function saveFromGrid(gridSelector, gridName) {
    const prefs = serialize(gridSelector);
    saveLocal(gridName, prefs);
    if (CONFIG.mode === 'server') return saveServer(gridName, prefs);
    return $.Deferred().resolve().promise();
  }

  // ─── Rekonsiliasi terhadap colModel yang berlaku ──────────────

  function toOrder(v) {
    return Number.isFinite(v) ? v : Number.MAX_SAFE_INTEGER;
  }

  /**
   * Cocokkan preferensi tersimpan dengan colModel yang berlaku sekarang.
   *
   *  - Kolom yang ada di preferensi tapi sudah tidak ada di colModel dibuang.
   *    Ini yang mencegah remapColumns menerima indeks -1 dan merusak grid.
   *  - Kolom yang ada di colModel tapi belum ada di preferensi disisipkan tepat
   *    setelah tetangga kirinya di colModel asli -- bukan dilempar ke ujung
   *    kanan, dan bukan pula hilang dari panel pengaturan.
   *
   * Selalu mengembalikan daftar lengkap sepanjang colModel, order 0..n-1.
   */
  function reconcile(baseColModel, savedPrefs) {
    const base = (Array.isArray(baseColModel) ? baseColModel : [])
      .filter(c => c && c.name && !isSystemCol(c.name));
    if (!base.length) return [];

    const baseIndex = new Map();
    base.forEach((c, i) => { if (!baseIndex.has(c.name)) baseIndex.set(c.name, i); });

    const prefMap = new Map();
    (Array.isArray(savedPrefs) ? savedPrefs : []).forEach(p => {
      if (p && p.name && !prefMap.has(p.name)) prefMap.set(p.name, p);
    });

    // 1. Nama tersimpan yang masih dikenal, diurutkan sesuai order tersimpan.
    const ordered = [...prefMap.values()]
      .filter(p => baseIndex.has(p.name))
      .sort((a, b) => toOrder(a.order) - toOrder(b.order))
      .map(p => p.name);

    // 2. Sisipkan kolom yang belum punya preferensi di posisi alaminya.
    base.forEach((col, i) => {
      if (ordered.includes(col.name)) return;
      let at = 0;
      for (let j = i - 1; j >= 0; j--) {
        const idx = ordered.indexOf(base[j].name);
        if (idx !== -1) { at = idx + 1; break; }
      }
      ordered.splice(at, 0, col.name);
    });

    return ordered.map((name, idx) => {
      const col = base[baseIndex.get(name)];
      const pref = prefMap.get(name);
      const width = pref ? normalizeWidth(pref.width) : undefined;
      return {
        name: name,
        width: width !== undefined ? width : normalizeWidth(col.width),
        hidden: pref && typeof pref.hidden === 'boolean' ? pref.hidden : !!col.hidden,
        frozen: pref && typeof pref.frozen === 'boolean' ? pref.frozen : !!col.frozen,
        order: idx,
      };
    });
  }

  // ─── Merge ke colModel ────────────────────────────────────────

  /**
   * @param {Array}  baseColModel
   * @param {Array}  savedPrefs
   * @param {Object} [options]
   * @param {boolean} [options.preserveOrder] Terapkan lebar/sembunyi/freeze saja
   *        dan biarkan urutan colModel apa adanya. Dipakai grid gaya lama yang
   *        memasangkan judul lewat array `colNames` terpisah: menyusun ulang
   *        colModel tanpa ikut menyusun ulang colNames membuat header tertukar.
   */
  function applyToColModel(baseColModel, savedPrefs, options) {
    if (!Array.isArray(baseColModel) || !baseColModel.length) return baseColModel;
    if (!savedPrefs || !savedPrefs.length) return baseColModel;

    const prefs = reconcile(baseColModel, savedPrefs);
    if (!prefs.length) return baseColModel;

    if (options && options.preserveOrder) {
      const prefByName = new Map(prefs.map(p => [p.name, p]));
      return baseColModel.map(col => {
        const p = col && col.name ? prefByName.get(col.name) : null;
        if (!p) return col;
        return {
          ...col,
          width: p.width !== undefined ? p.width : col.width,
          hidden: p.hidden,
          frozen: p.frozen,
        };
      });
    }

    const colByName = new Map();
    baseColModel.forEach(col => {
      if (col && col.name && !colByName.has(col.name)) colByName.set(col.name, col);
    });

    const updated = prefs.map(p => {
      const col = colByName.get(p.name);
      colByName.delete(p.name);
      return {
        ...col,
        width: p.width !== undefined ? p.width : col.width,
        hidden: p.hidden,
        frozen: p.frozen,
      };
    });

    // Apa pun yang tidak tersentuh rekonsiliasi (mis. entri tanpa name)
    // dipertahankan di belakang, jangan sampai kolom hilang diam-diam.
    baseColModel.forEach(col => {
      if (!col || !col.name || colByName.has(col.name)) updated.push(col);
    });

    return updated;
  }

  // ─── Listener resizeStop ──────────────────────────────────────

  function attachListeners(gridSelector, gridName, onSaved) {
    const grid = $(gridSelector);

    // Cegah dobel-hook kalau init sempat berjalan dua kali; tanpa ini satu
    // resize bisa memicu dua rantai simpan.
    if (grid.data('gridPrefAttached')) return;
    grid.data('gridPrefAttached', true);

    const debouncedSave = debounce(function () {
      saveFromGrid(gridSelector, gridName).then(function () {
        if (typeof onSaved === 'function') onSaved(serialize(gridSelector));
      });
    }, CONFIG.debounceMs);

    const originalResizeStop = grid.jqGrid('getGridParam', 'resizeStop');
    grid.jqGrid('setGridParam', {
      resizeStop: function (newWidth, index) {
        if (typeof originalResizeStop === 'function') {
          originalResizeStop.call(this, newWidth, index);
        }
        debouncedSave();
      }
    });
  }

  // ─── Reset ────────────────────────────────────────────────────

  function reset(gridName) {
    clearLocal(gridName);
    if (CONFIG.mode === 'server') {
      deleteServer(gridName);
    }
  }

  // ─── API Publik ───────────────────────────────────────────────

  return {
    configure(options) {
      Object.assign(CONFIG, options);
    },

    async load(gridName) {
      const local = loadLocal(gridName);
      if (local) return local;

      if (CONFIG.mode === 'server') {
        const serverPrefs = await loadServer(gridName);
        if (serverPrefs) saveLocal(gridName, serverPrefs);
        return serverPrefs ?? null;
      }
      return null;
    },

    loadSync(gridName) {
      return loadLocal(gridName);
    },

    save(gridName, prefs) {
      saveLocal(gridName, prefs);
      if (CONFIG.mode === 'server') return saveServer(gridName, prefs);
    },

    saveFromGrid,
    serialize,
    reconcile,
    apply: applyToColModel,
    extract: serialize,   // nama lama, dipertahankan untuk pemanggil luar
    attach: attachListeners,
    reset,
    _localKey: localKey,
    _version: STORAGE_VERSION,
  };

})();
