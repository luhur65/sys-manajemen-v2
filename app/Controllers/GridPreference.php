<?php

namespace App\Controllers;

use CodeIgniter\Controller;

/**
 * Penyimpanan preferensi kolom grid per user.
 *
 * Bentuk data yang ditulis klien (v2):
 *   { "v": 2, "updatedAt": 1690000000000, "columns": [ {name,width,hidden,frozen,order}, ... ] }
 * Format lama (v1) berupa array kolom telanjang tanpa amplop masih dibaca apa
 * adanya supaya data yang sudah tersimpan tidak hangus.
 */
class GridPreference extends BaseController
{
    /** Versi amplop yang ditulis controller saat memigrasi kunci lama. */
    private const STORAGE_VERSION = 2;

    // ─── Batas payload ───────────────────────────────────────────────────────
    //
    // Tanpa batas-batas ini berkas per user tidak punya batas atas sama sekali:
    // apa pun yang dikirim klien ditulis mentah, dengan kunci sebanyak apa pun.
    // Padahal setiap penyimpanan menulis ulang SELURUH berkas, jadi payload yang
    // membengkak langsung menjadi beban tulis pada tiap operasi berikutnya.
    //
    // Angkanya dipilih longgar terhadap kenyataan: 33 grid, kolom terbanyak 25,
    // dan nama kolom terpanjang 26 karakter di seluruh aplikasi.

    /** Kunci grid: "controller.method.gridId", plus kunci lama ber-underscore. */
    private const GRID_NAME_PATTERN = '/^[A-Za-z0-9._-]{1,120}$/';

    /** Nama kolom jqGrid. Seluruh 97 nama yang dipakai aplikasi masuk pola ini. */
    private const COL_NAME_PATTERN = '/^[A-Za-z0-9._-]{1,100}$/';

    private const MAX_GRIDS_PER_USER = 200;
    private const MAX_COLUMNS        = 200;
    private const MIN_WIDTH          = 1;
    private const MAX_WIDTH          = 5000;

    /**
     * Memastikan request hanya bisa diakses oleh user yang sudah login
     */
    private function getUserId()
    {
        $userid = session()->get(SESSION_NAME . 'userid');
        if (!$userid) {
            header('HTTP/1.1 401 Unauthorized');
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        return $userid;
    }

    /**
     * Lokasi file JSON unik untuk tiap user
     */
    private function getFilePath($userid)
    {
        $dir = WRITEPATH . 'grid_preferences';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        return $dir . '/' . md5($userid) . '.json';
    }

    /**
     * Simpan salinan berkas yang tidak bisa di-decode, lalu laporkan.
     *
     * Sebelumnya `json_decode(...) ?: []` membuat berkas rusak diam-diam
     * dianggap kosong, dan penyimpanan berikutnya menimpanya — seluruh grid
     * milik user itu hilang tanpa jejak. Satu berkas menampung SEMUA grid,
     * jadi kerugiannya tumbuh seiring modul yang diaktifkan.
     */
    private function quarantine(string $file, string $raw): void
    {
        $copy = $file . '.corrupt-' . date('Ymd-His');
        @file_put_contents($copy, $raw);
        log_message('error', 'GridPreference: berkas rusak, disalin ke ' . basename($copy));
    }

    /**
     * Membaca file JSON di bawah kunci baca bersama.
     */
    private function loadData($userid)
    {
        $file = $this->getFilePath($userid);
        if (!file_exists($file)) {
            return [];
        }

        $fp = @fopen($file, 'r');
        if ($fp === false) {
            return [];
        }

        try {
            if (!flock($fp, LOCK_SH)) {
                return [];
            }
            $raw = stream_get_contents($fp);
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }

        if ($raw === '' || $raw === false) {
            return [];
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            $this->quarantine($file, (string) $raw);
            return [];
        }

        return $data;
    }

    /**
     * Baca-ubah-tulis seluruh berkas di bawah SATU kunci eksklusif.
     *
     * Berkas ini menampung semua grid milik satu user, jadi setiap penyimpanan
     * menulis ulang keseluruhannya. Tanpa kunci, dua penyimpanan yang berbarengan
     * — dua tab, atau dua grid pada satu halaman — saling menimpa, dan tulisan
     * yang terpotong membuat berkas tidak bisa di-decode. Peluangnya naik seiring
     * jumlah grid yang diaktifkan, jadi baca dan tulis harus berada di dalam
     * kunci yang sama, bukan dua operasi terpisah.
     *
     * @param callable(array): (array|null) $mutator kembalikan array baru, atau
     *        null bila tidak ada yang perlu ditulis
     */
    private function mutateData($userid, callable $mutator): bool
    {
        $file = $this->getFilePath($userid);

        $fp = @fopen($file, 'c+');
        if ($fp === false) {
            log_message('error', 'GridPreference: tidak bisa membuka ' . basename($file));
            return false;
        }

        try {
            if (!flock($fp, LOCK_EX)) {
                log_message('error', 'GridPreference: gagal mengunci ' . basename($file));
                return false;
            }

            $raw  = stream_get_contents($fp);
            $data = [];
            if (is_string($raw) && $raw !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $data = $decoded;
                } else {
                    $this->quarantine($file, $raw);
                }
            }

            $next = $mutator($data);
            if ($next === null) {
                return true;
            }

            $json = json_encode($next);
            if ($json === false) {
                log_message('error', 'GridPreference: gagal encode JSON untuk ' . basename($file));
                return false;
            }

            rewind($fp);
            ftruncate($fp, 0);
            $written = fwrite($fp, $json);
            fflush($fp);

            // Disk penuh / tulisan terpotong: berkas sudah dikosongkan, jadi
            // pulihkan isi lama supaya preferensi user tidak hilang.
            if ($written !== strlen($json)) {
                log_message('error', 'GridPreference: gagal menulis ' . basename($file) . " ({$written}/" . strlen($json) . ' byte).');
                if (is_string($raw) && $raw !== '') {
                    rewind($fp);
                    ftruncate($fp, 0);
                    fwrite($fp, $raw);
                    fflush($fp);
                }
                return false;
            }

            return true;
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }

    // ─── Migrasi kunci lama ──────────────────────────────────────────────────

    /**
     * Kunci lama diturunkan dari window.location.pathname di browser: setiap
     * karakter non-alfanumerik diganti '_', lalu ditambah '_' + gridId. Contoh
     * nyata dari data yang sudah tersimpan:
     *
     *     "_sys_modern_piutangemkl_index_jqGrid"
     *
     * Kunci itu rapuh: satu halaman bisa dicapai lewat beberapa URL (Routes.php
     * mendaftarkan 'Piutangemkl', 'piutangemkl', 'Piutangemkl/index' dan
     * 'piutangemkl/index' untuk controller yang sama) dan base path aplikasi
     * ikut terbawa, jadi preferensi hangus begitu docroot berpindah.
     *
     * Kunci baru dibentuk server-side dari router: "piutangemkl.index.jqGrid".
     *
     * Sebuah kunci lama dianggap milik kunci baru bila ia berakhiran
     * '_<gridId>' dan sisa segmennya (huruf kecil) berakhiran
     * [controller, method] -- atau berakhiran [controller] saja ketika methodnya
     * 'index', karena '/piutangemkl' dan '/piutangemkl/index' adalah halaman
     * yang sama.
     *
     * Aturan yang sama diterapkan di sisi klien, lihat legacyTailMatches() di
     * public/libraries/tas-lib/js/GridPreferenceManager.js.
     *
     * @return list<string> kunci lama yang merujuk grid yang sama
     */
    private function legacyKeysFor(string $gridName, array $data): array
    {
        $parts = explode('.', $gridName);
        if (count($parts) < 3) {
            return [];
        }

        $gridId     = array_pop($parts);
        $method     = strtolower(array_pop($parts));
        $controller = strtolower(array_pop($parts));
        if ($gridId === '' || $method === '' || $controller === '') {
            return [];
        }

        $suffix = '_' . $gridId;
        $found  = [];

        foreach (array_keys($data) as $key) {
            if (!is_string($key) || $key === $gridName) {
                continue;
            }
            if (!str_ends_with($key, $suffix)) {
                continue;
            }

            $tail = substr($key, 0, -strlen($suffix));
            $segs = array_values(array_filter(explode('_', strtolower($tail)), 'strlen'));
            $n    = count($segs);

            $matches = ($n >= 2 && $segs[$n - 2] === $controller && $segs[$n - 1] === $method)
                || ($method === 'index' && $n >= 1 && $segs[$n - 1] === $controller);

            if ($matches) {
                $found[] = $key;
            }
        }

        return $found;
    }

    /**
     * Ambil daftar kolom dari sebuah entri, menerima amplop v2 maupun array
     * telanjang v1.
     */
    private function columnsOf($entry): ?array
    {
        if (!is_array($entry) || $entry === []) {
            return null;
        }
        if (isset($entry['columns'])) {
            return is_array($entry['columns']) && $entry['columns'] !== [] ? $entry['columns'] : null;
        }
        return array_is_list($entry) ? $entry : null;
    }

    // ─── Validasi payload ────────────────────────────────────────────────────

    private function isValidGridName($gridName): bool
    {
        return is_string($gridName) && preg_match(self::GRID_NAME_PATTERN, $gridName) === 1;
    }

    /**
     * Ubah kiriman klien menjadi amplop kanonik, atau tolak seluruhnya.
     *
     * Tidak ada satu pun nilai dari klien yang dipercaya apa adanya:
     *
     *  - Nama kolom harus lolos whitelist. Ia berakhir di atribut HTML dan
     *    dibandingkan dengan colModel, jadi ia tidak boleh berisi apa pun selain
     *    identifier. Panel tetap meng-escape saat render sebagai lapis kedua.
     *  - `width` dijepit ke rentang masuk akal; nilai di luar itu dibuang, bukan
     *    membatalkan seluruh payload, supaya satu angka aneh tidak menghapus
     *    tata letak yang sudah benar.
     *  - `hidden`/`frozen` dipaksa boolean.
     *  - `order` dihitung ulang dari posisi setelah pengurutan, jadi nilai yang
     *    dikirim klien tidak pernah menjadi sumber kebenaran.
     *  - Nama ganda dibuang; kemunculan pertama yang menang.
     *
     * @return array|null amplop siap simpan, atau null bila payload ditolak
     */
    private function sanitizePreferences($raw): ?array
    {
        $columns = $this->columnsOf(is_array($raw) ? $raw : []);
        if ($columns === null || count($columns) > self::MAX_COLUMNS) {
            return null;
        }

        // Urutkan dulu berdasar `order` bila ada, baru posisinya dijadikan
        // kebenaran. Klien memang selalu mengirim terurut, tapi jangan
        // bergantung pada itu. Sort PHP 8 stabil, jadi entri tanpa `order`
        // mempertahankan urutan kedatangannya.
        usort($columns, static function ($a, $b) {
            $oa = is_array($a) && isset($a['order']) && is_numeric($a['order']) ? (int) $a['order'] : PHP_INT_MAX;
            $ob = is_array($b) && isset($b['order']) && is_numeric($b['order']) ? (int) $b['order'] : PHP_INT_MAX;

            return $oa <=> $ob;
        });

        $clean = [];
        $seen  = [];

        foreach ($columns as $col) {
            if (!is_array($col) || !isset($col['name'])) {
                return null;
            }

            $name = $col['name'];
            if (!is_string($name) || preg_match(self::COL_NAME_PATTERN, $name) !== 1) {
                return null;
            }
            if (isset($seen[$name])) {
                continue;
            }
            $seen[$name] = true;

            $entry = ['name' => $name];

            if (isset($col['width'])) {
                $width = filter_var($col['width'], FILTER_VALIDATE_INT);
                if ($width !== false && $width >= self::MIN_WIDTH && $width <= self::MAX_WIDTH) {
                    $entry['width'] = $width;
                }
            }

            $entry['hidden'] = !empty($col['hidden']);
            $entry['frozen'] = !empty($col['frozen']);
            $entry['order']  = count($clean);

            $clean[] = $entry;
        }

        if ($clean === []) {
            return null;
        }

        return [
            'v'         => self::STORAGE_VERSION,
            'updatedAt' => (int) round(microtime(true) * 1000),
            'columns'   => $clean,
        ];
    }

    /**
     * Jaga jumlah grid per user tetap terbatas. Pemakaian normal mentok di 33;
     * batas ini hanya menggigit kunci yang dibuat-buat. Yang dibuang adalah yang
     * paling lama tidak disentuh, jadi tata letak yang aktif dipakai selamat.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function pruneExcessGrids(array $data, string $keep): array
    {
        if (count($data) <= self::MAX_GRIDS_PER_USER) {
            return $data;
        }

        $ages = [];
        foreach ($data as $key => $entry) {
            $ages[$key] = is_array($entry) && isset($entry['updatedAt']) && is_numeric($entry['updatedAt'])
                ? (int) $entry['updatedAt']
                : 0;
        }
        asort($ages);

        $buang = count($data) - self::MAX_GRIDS_PER_USER;
        foreach (array_keys($ages) as $key) {
            if ($buang <= 0) {
                break;
            }
            if ($key === $keep) {
                continue;
            }
            unset($data[$key]);
            $buang--;
        }

        log_message('warning', 'GridPreference: melewati batas ' . self::MAX_GRIDS_PER_USER . ' grid, entri terlama dibuang.');

        return $data;
    }

    /**
     * Pindahkan preferensi dari kunci lama ke kunci baru, sekali jalan.
     * Idempoten: pemeriksaan diulang di dalam kunci eksklusif, jadi dua tab yang
     * membuka aplikasi berbarengan di browser baru tidak saling menimpa.
     *
     * @return array|null amplop yang sudah dipindah, atau null bila tidak ada
     */
    private function migrateLegacyKeys($userid, string $gridName): ?array
    {
        $envelope = null;

        $this->mutateData($userid, function (array $data) use ($gridName, &$envelope) {
            // Request lain mungkin sudah memigrasi lebih dulu.
            if (isset($data[$gridName])) {
                $envelope = $data[$gridName];
                return null;
            }

            $legacy = $this->legacyKeysFor($gridName, $data);
            if ($legacy === []) {
                return null;
            }

            $best = null;
            foreach ($legacy as $key) {
                $columns = $this->columnsOf($data[$key]);
                // Ambil kandidat terkaya bila ada beberapa ejaan URL untuk grid yang sama.
                if ($columns !== null && ($best === null || count($columns) > count($best))) {
                    $best = $columns;
                }
                unset($data[$key]);
            }

            if ($best !== null) {
                $envelope = [
                    'v'         => self::STORAGE_VERSION,
                    'updatedAt' => (int) round(microtime(true) * 1000),
                    'columns'   => $best,
                ];
                $data[$gridName] = $envelope;
            }

            log_message('info', 'GridPreference: migrasi ' . count($legacy) . ' kunci lama ke "' . $gridName . '".');

            return $data;
        });

        return $envelope;
    }

    // ─── Endpoint ────────────────────────────────────────────────────────────

    /**
     * GET /gridpreference/load?grid_name=controller.method.gridId
     */
    public function load()
    {
        $userid   = $this->getUserId();
        $gridName = $this->request->getGet('grid_name');

        if (!$this->isValidGridName($gridName)) {
            return $this->response->setJSON(['preferences' => null]);
        }

        $data = $this->loadData($userid);

        if (isset($data[$gridName])) {
            return $this->response->setJSON(['preferences' => $data[$gridName]]);
        }

        // Hanya ambil kunci eksklusif kalau memang ada yang perlu dipindah;
        // pembacaan biasa tidak boleh ikut menahan penulis.
        if ($this->legacyKeysFor($gridName, $data) !== []) {
            $migrated = $this->migrateLegacyKeys($userid, $gridName);
            if ($migrated !== null) {
                return $this->response->setJSON(['preferences' => $migrated]);
            }
        }

        return $this->response->setJSON(['preferences' => null]);
    }

    /**
     * POST /gridpreference/save
     * Body JSON: { "grid_name": "...", "preferences": { v, updatedAt, columns } }
     */
    public function save()
    {
        $userid = $this->getUserId();
        $json   = $this->request->getJSON(true);

        if (!is_array($json) || !isset($json['grid_name'], $json['preferences'])) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid payload']);
        }

        $gridName = $json['grid_name'];
        if (!$this->isValidGridName($gridName)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid grid name']);
        }

        // Yang disimpan adalah hasil sanitasi, bukan kiriman mentah.
        $preferences = $this->sanitizePreferences($json['preferences']);
        if ($preferences === null) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid preferences']);
        }

        $ok = $this->mutateData($userid, function (array $data) use ($gridName, $preferences) {
            // Buang kunci lama yang merujuk grid yang sama, supaya satu grid
            // tidak menyimpan dua salinan preferensi.
            foreach ($this->legacyKeysFor($gridName, $data) as $key) {
                unset($data[$key]);
            }

            $data[$gridName] = $preferences;

            return $this->pruneExcessGrids($data, $gridName);
        });

        if (!$ok) {
            return $this->response->setStatusCode(503)->setJSON(['error' => 'Storage unavailable']);
        }

        return $this->response->setJSON(['success' => true]);
    }

    /**
     * POST /gridpreference/delete
     * Body JSON: { "grid_name": "..." }
     *
     * Dulu endpoint ini GET. Filter csrf CI4 hanya memeriksa POST/PUT/PATCH/
     * DELETE, jadi versi GET-nya bisa dipicu pihak ketiga lewat <img src="...">
     * dan menghapus preferensi user yang sedang login.
     */
    public function delete()
    {
        $userid   = $this->getUserId();
        $json     = $this->request->getJSON(true);
        $gridName = is_array($json) && isset($json['grid_name']) ? $json['grid_name'] : null;

        if ($this->isValidGridName($gridName)) {
            $this->mutateData($userid, function (array $data) use ($gridName) {
                $changed = false;

                foreach (array_merge([$gridName], $this->legacyKeysFor($gridName, $data)) as $key) {
                    if (isset($data[$key])) {
                        unset($data[$key]);
                        $changed = true;
                    }
                }

                return $changed ? $data : null;
            });
        }

        return $this->response->setJSON(['success' => true]);
    }
}
