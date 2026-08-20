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
     *                         - `'FNominal' => ['sql' => 'CAST(FNominal AS VARCHAR)', 'numeric' => true]`
     *                           `numeric` menghapus pemisah ribuan dari input user.
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
     * @return array<string, array{sql: string, numeric: bool}>
     */
    private function normalizeFieldMap(array $fieldMap): array
    {
        $allowed = [];

        foreach ($fieldMap as $key => $value) {
            if (is_int($key)) {
                $field = trim((string) $value);
                $spec  = ['sql' => $field, 'numeric' => false];
            } else {
                $field = trim((string) $key);

                $spec = is_array($value)
                    ? [
                        'sql'     => trim((string) ($value['sql'] ?? $field)),
                        'numeric' => ! empty($value['numeric']),
                    ]
                    : ['sql' => trim((string) $value), 'numeric' => false];
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
     * @param array<string, array{sql: string, numeric: bool}> $allowed
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
        $column    = $allowed[$field]['sql'];
        $data      = isset($rule->data) ? (string) $rule->data : '';

        if ($allowed[$field]['numeric']) {
            // Grid menampilkan angka dengan pemisah ribuan; kolomnya sendiri tidak.
            $data = str_replace(',', '', $data);
        }

        // Operator tanpa nilai.
        if ($operation === 'nu') {
            return $column . " = ''";
        }

        if ($operation === 'nn') {
            return $column . " != ''";
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
