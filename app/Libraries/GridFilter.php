<?php

namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

/**
 * Parser filter jqGrid yang aman terhadap SQL Injection.
 *
 * Menggantikan seluruh implementasi `operation()` / `operationAll()` lama yang
 * mengkonkatenasi nama kolom dan nilai dari client langsung ke string SQL.
 *
 * Tiga lapis pertahanan:
 *  1. Nama kolom WAJIB lolos whitelist milik controller. Nilai whitelist adalah
 *     ekspresi SQL yang ditulis developer, tidak pernah berasal dari request.
 *  2. Operator WAJIB lolos whitelist ({@see self::OPERATORS}); operator asing
 *     membuat rule dibuang.
 *  3. Nilai selalu di-escape lewat koneksi database yang akan mengeksekusi query
 *     tersebut, sehingga selalu menjadi string literal, bukan bagian sintaks SQL.
 *
 * PENTING: koneksi yang diberikan ke constructor harus koneksi yang benar-benar
 * menjalankan query-nya. Aplikasi ini memakai lebih dari satu grup database
 * (`default` dan `dbtruck`) dan aturan escaping tiap driver berbeda (SQL Server
 * menggandakan kutip, MySQL memakai backslash). Meng-escape dengan driver yang
 * salah membuka kembali celah injeksi.
 */
class GridFilter
{
    /**
     * Operator jqGrid -> template SQL.
     * `%s` selalu diisi nilai yang sudah di-escape (lengkap dengan kutipnya).
     */
    private const OPERATORS = [
        'eq' => '= %s',
        'ne' => '!= %s',
        'lt' => '< %s',
        'gt' => '> %s',
        'le' => '<= %s',
        'ge' => '>= %s',
        'bw' => 'LIKE %s',
        'bn' => 'NOT LIKE %s',
        'ew' => 'LIKE %s',
        'en' => 'NOT LIKE %s',
        'cn' => 'LIKE %s',
        'nc' => 'NOT LIKE %s',
    ];

    /**
     * Operator pencarian teks. Hanya operator inilah yang dicocokkan ke angka
     * BERFORMAT; sisanya (eq/lt/gt dsb.) tetap perbandingan numerik.
     */
    private const LIKE_OPERATORS = ['bw', 'bn', 'ew', 'en', 'cn', 'nc'];

    protected BaseConnection $db;

    /**
     * @param BaseConnection|string|null $db Koneksi, nama grup database, atau
     *                                       null untuk memakai grup default.
     */
    public function __construct($db = null)
    {
        $this->db = $db instanceof BaseConnection ? $db : Database::connect($db);
    }

    /**
     * Membangun potongan kondisi WHERE dari JSON filter jqGrid.
     *
     * @param mixed $filters   Isi POST `filters` (JSON) apa adanya.
     * @param array $fieldMap  Whitelist kolom. Bentuk yang diterima:
     *                         - `'FNShipper'`                      kolom polos
     *                         - `'FTgl' => "FORMAT(FTgl, 'x')"`    ekspresi SQL
     *                         - `'FNominal' => ['sql' => 'FNominal', 'numeric' => true]`
     *                           `numeric` menandai kolom yang di grid tampil
     *                           dengan pemisah ribuan; `sql` harus ekspresi
     *                           angka (bukan CAST ke varchar).
     *                         - `'FOmset' => ['sql' => 'FOmset', 'numeric' => true, 'decimals' => 2]`
     *                           `decimals` menyamakan jumlah angka desimal
     *                           dengan formatter kolom di jqGrid.
     *
     * @return string Kondisi tanpa kurung dan tanpa `AND` di depan, atau string
     *                kosong bila tidak ada satu pun rule yang valid.
     */
    public function build($filters, array $fieldMap): string
    {
        $allowed = $this->normalizeFieldMap($fieldMap);

        if ($allowed === []) {
            log_message('error', '[GridFilter] Whitelist kolom kosong, seluruh filter grid diabaikan.');

            return '';
        }

        $decoded = $this->decode($filters);

        if ($decoded === null) {
            return '';
        }

        $groupOperation = strtoupper(trim((string) ($decoded->groupOp ?? 'AND'))) === 'OR' ? 'OR' : 'AND';

        $conditions = [];

        foreach ($decoded->rules as $rule) {
            $condition = $this->buildCondition($rule, $allowed);

            if ($condition !== null) {
                $conditions[] = $condition;
            }
        }

        return $conditions === [] ? '' : implode(' ' . $groupOperation . ' ', $conditions);
    }

    /**
     * Decode JSON filter jqGrid.
     *
     * Beberapa view lama mengirim JSON yang ter-escape ganda, jadi pembersihan
     * yang sama seperti kode lama tetap dipertahankan.
     *
     * @return object|null Object dengan properti `rules` (array) dan `groupOp`.
     */
    private function decode($filters): ?object
    {
        if (! is_string($filters) || trim($filters) === '') {
            return null;
        }

        $json = str_replace(['\"', '"[', ']"'], ['"', '[', ']'], $filters);

        $decoded = json_decode($json);

        if (! is_object($decoded) || ! isset($decoded->rules) || ! is_array($decoded->rules) || $decoded->rules === []) {
            return null;
        }

        return $decoded;
    }

    /**
     * Menyusun whitelist menjadi bentuk seragam, dikunci dengan nama kolom
     * huruf kecil supaya pencocokan tidak bergantung besar-kecil huruf.
     *
     * @return array<string, array{sql: string, numeric: bool, decimals: int}>
     */
    private function normalizeFieldMap(array $fieldMap): array
    {
        $allowed = [];

        foreach ($fieldMap as $key => $value) {
            if (is_int($key)) {
                $field = trim((string) $value);
                $spec  = ['sql' => $field, 'numeric' => false, 'decimals' => 0];
            } else {
                $field = trim((string) $key);

                $spec = is_array($value)
                    ? [
                        'sql'      => trim((string) ($value['sql'] ?? $field)),
                        'numeric'  => ! empty($value['numeric']),
                        'decimals' => max(0, min(6, (int) ($value['decimals'] ?? 0))),
                    ]
                    : ['sql' => trim((string) $value), 'numeric' => false, 'decimals' => 0];
            }

            // Nama kolom yang dikirim client harus identifier polos. Ekspresi SQL
            // hanya boleh berada di sisi nilai (ditulis developer), bukan di kunci.
            if ($field === '' || $spec['sql'] === '' || preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $field) !== 1) {
                log_message('error', '[GridFilter] Entri whitelist tidak valid diabaikan: ' . $field);

                continue;
            }

            $allowed[strtolower($field)] = $spec;
        }

        return $allowed;
    }

    /**
     * @param array<string, array{sql: string, numeric: bool, decimals: int}> $allowed
     *
     * @return string|null null bila rule ditolak.
     */
    private function buildCondition($rule, array $allowed): ?string
    {
        if (! is_object($rule)) {
            return null;
        }

        $field = isset($rule->field) ? strtolower(trim((string) $rule->field)) : '';

        if ($field === '' || ! isset($allowed[$field])) {
            log_message('warning', '[GridFilter] Filter pada kolom tidak dikenal ditolak: ' . (string) ($rule->field ?? ''));

            return null;
        }

        $operation = isset($rule->op) ? strtolower(trim((string) $rule->op)) : '';
        $spec      = $allowed[$field];
        $column    = $spec['sql'];
        $data      = isset($rule->data) ? (string) $rule->data : '';

        // Operator tanpa nilai.
        if ($operation === 'nu') {
            return $column . " = ''";
        }

        if ($operation === 'nn') {
            return $column . " != ''";
        }

        if ($spec['numeric']) {
            $display = in_array($operation, self::LIKE_OPERATORS, true)
                ? $this->numericDisplayExpression($spec)
                : null;

            if ($display !== null) {
                // Pencarian teks dicocokkan ke angka BERFORMAT, persis seperti
                // yang dilihat user di grid dan yang disorot setHighlight().
                // Tanpa ini, mengetik ",3" dicari sebagai "3" sehingga setiap
                // baris yang punya angka 3 di mana pun ikut lolos walau tidak
                // ter-highlight.
                $column = $display;
            } else {
                // Perbandingan numerik (eq/lt/gt/in/...): pemisah ribuan yang
                // diketik user dibuang supaya nilainya bisa dibandingkan.
                $data = str_replace(',', '', $data);
            }
        }

        // Operator dengan daftar nilai.
        if ($operation === 'in' || $operation === 'ni') {
            $list = $this->escapeList($data);

            if ($list === '') {
                return null;
            }

            return $column . ($operation === 'in' ? ' IN (' : ' NOT IN (') . $list . ')';
        }

        if (! isset(self::OPERATORS[$operation])) {
            log_message('warning', '[GridFilter] Operator filter tidak dikenal ditolak: ' . $operation);

            return null;
        }

        $value = match ($operation) {
            'bw', 'bn' => $data . '%',
            'ew', 'en' => '%' . $data,
            'cn', 'nc' => '%' . $data . '%',
            default    => $data,
        };

        return $column . ' ' . sprintf(self::OPERATORS[$operation], $this->db->escape($value));
    }

    /**
     * Ekspresi SQL yang menghasilkan angka persis seperti yang dirender jqGrid
     * (pemisah ribuan koma, jumlah desimal mengikuti formatter kolom), supaya
     * hasil filter LIKE sama dengan yang di-highlight di layar.
     *
     * Ekspresi hanya disusun dari `sql` milik whitelist (ditulis developer) dan
     * `decimals` yang sudah dipaksa jadi integer, jadi tidak ada nilai request
     * yang masuk ke sini.
     *
     * @param array{sql: string, numeric: bool, decimals: int} $spec
     *
     * @return string|null null bila driver-nya tidak punya fungsi format angka;
     *                     pemanggil lalu memakai perilaku lama (buang koma).
     */
    private function numericDisplayExpression(array $spec): ?string
    {
        $decimals = $spec['decimals'];

        return match ($this->db->DBDriver) {
            'SQLSRV' => sprintf(
                "FORMAT(%s, '%s', 'en-US')",
                $spec['sql'],
                $decimals > 0 ? '#,##0.' . str_repeat('0', $decimals) : '#,##0'
            ),
            'MySQLi', 'mysqli' => sprintf("FORMAT(%s, %d, 'en_US')", $spec['sql'], $decimals),
            default            => null,
        };
    }

    /**
     * Memecah daftar nilai `IN` menjadi literal yang masing-masing di-escape.
     * Kode lama menyisipkan isi `data` mentah-mentah ke dalam kurung `IN (...)`.
     */
    private function escapeList(string $data): string
    {
        $items = [];

        foreach (explode(',', $data) as $item) {
            // Client boleh mengirim `'A','B'` maupun `A,B`; kutip bawaannya dibuang
            // lalu dipasang ulang oleh escape().
            $item = trim(trim($item), "'\"");
            $item = trim($item);

            if ($item === '') {
                continue;
            }

            $items[] = $this->db->escape($item);
        }

        return implode(', ', $items);
    }
}
