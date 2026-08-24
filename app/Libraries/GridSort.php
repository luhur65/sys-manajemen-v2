<?php

namespace App\Libraries;

/**
 * Sanitasi klausa ORDER BY dan paging untuk grid jqGrid.
 *
 * Nama kolom tidak bisa dijadikan bind parameter, sehingga `sidx` dan `sord`
 * yang datang dari client harus divalidasi sebelum masuk ke string SQL.
 * Sebelumnya keduanya dikonkatenasi mentah-mentah di 24 model (H-03).
 *
 * Dua lapis:
 *  1. {@see self::column()} hanya meloloskan identifier polos (`Kolom` atau
 *     `tabel.Kolom`). Apa pun yang mengandung spasi, kutip, koma, atau tanda
 *     kurung otomatis gugur ke nilai default yang ditulis developer — jadi
 *     tidak ada jalan menyelipkan sintaks SQL lewat parameter ini.
 *  2. Bila model memberi daftar `$sortable`, kolomnya juga harus ada di daftar
 *     itu. Ini mencegah pengurutan lewat kolom yang tidak tampil di grid —
 *     jalur inferensi buta terhadap kolom sensitif seperti `password`.
 *
 * Nilai `$default` SELALU dianggap tulisan developer dan dikembalikan apa
 * adanya, sehingga default multi-kolom yang sudah ada tetap bekerja, contoh:
 * `"substring(FBulan,4,4) DESC, FBulan DESC, FNMarketing"`.
 */
final class GridSort
{
    /** `Kolom` atau `tabel.Kolom`. Sengaja ketat: huruf, angka, garis bawah. */
    private const IDENTIFIER = '/^[A-Za-z_][A-Za-z0-9_]{0,62}(\.[A-Za-z_][A-Za-z0-9_]{0,62})?$/';

    /**
     * Kolom pengurutan yang aman dipakai di ORDER BY.
     *
     * @param mixed        $sidx     Nilai `sidx` dari client, apa adanya.
     * @param string       $default  Ekspresi ORDER BY tulisan developer, dipakai
     *                               bila `$sidx` tidak lolos. Boleh multi-kolom.
     * @param list<string> $sortable Whitelist opsional (tidak peka besar-kecil huruf).
     *                               Kosong berarti cukup validasi identifier.
     */
    public static function column($sidx, string $default, array $sortable = []): string
    {
        if (! is_string($sidx) && ! is_numeric($sidx)) {
            return $default;
        }

        $candidate = trim((string) $sidx);

        if ($candidate === '' || preg_match(self::IDENTIFIER, $candidate) !== 1) {
            if ($candidate !== '') {
                log_message('warning', '[GridSort] sidx ditolak (bukan identifier): ' . $candidate);
            }

            return $default;
        }

        if ($sortable === []) {
            return $candidate;
        }

        foreach ($sortable as $allowed) {
            if (strcasecmp($allowed, $candidate) === 0) {
                // Kembalikan ejaan dari whitelist, bukan dari client.
                return $allowed;
            }
        }

        log_message('warning', '[GridSort] sidx di luar whitelist ditolak: ' . $candidate);

        return $default;
    }

    /**
     * Arah pengurutan; hanya ASC atau DESC yang mungkin keluar dari sini.
     *
     * @param mixed $sord Nilai `sord` dari client, apa adanya.
     */
    public static function direction($sord, string $default = 'DESC'): string
    {
        $candidate = is_string($sord) ? strtoupper(trim($sord)) : '';

        if ($candidate === 'ASC' || $candidate === 'DESC') {
            return $candidate;
        }

        return strtoupper($default) === 'ASC' ? 'ASC' : 'DESC';
    }

    /**
     * Jumlah baris per halaman sebagai integer.
     *
     * Sengaja tidak membatasi rentang: beberapa controller memakai `limit = 0`
     * sebagai penanda "tidak ada data", dan pembatasan ukuran halaman adalah
     * lingkup temuan M-06, bukan H-03. Cast ke int sudah cukup untuk menutup
     * injeksi.
     *
     * @param mixed $limit
     */
    public static function limit($limit): int
    {
        return (int) $limit;
    }

    /**
     * Offset baris sebagai integer non-negatif.
     *
     * @param mixed $start
     */
    public static function offset($start): int
    {
        return max(0, (int) $start);
    }
}
