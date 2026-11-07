<?php
namespace App\Models;

use CodeIgniter\Model;

/**
 * Laporan Piutang Overdue.
 * Seluruh data diambil dari stored procedure di database dbTruckWeb
 * (koneksi 'dbtruck'), tidak memakai query builder.
 */
class MpiutangoverdueModel extends Model
{
    protected $dbtruck;
    protected $table         = 'LapEMKL_OverDue'; // dummy, data dari stored procedure
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [];
    protected $useTimestamps = false;

    public function __construct()
    {
        parent::__construct();
        $this->dbtruck = \Config\Database::connect('dbtruck');
    }

    /**
     * Jalankan SP dan kembalikan kolom pertama tiap baris sebagai list string.
     */
    private function firstColumn(string $sql, array $binds = []): array
    {
        $rows = $this->dbtruck->query($sql, $binds)->getResultArray();
        $out  = [];
        foreach ($rows as $row) {
            $val = trim((string) reset($row));
            if ($val !== '' && !in_array($val, $out, true)) {
                $out[] = $val;
            }
        }
        return $out;
    }

    public function getCabang(): array
    {
        return $this->firstColumn('EXEC usp_getcabangoverdue');
    }

    public function getBulan(string $cabang): array
    {
        return $this->firstColumn('EXEC usp_getbulanoverdue ?', [$cabang]);
    }

    public function getMarketing(string $cabang, string $bulan): array
    {
        return $this->firstColumn('EXEC usp_getmarketingoverdue ?, ?', [$cabang, $bulan]);
    }

    public function getShipper(string $cabang, string $bulan, string $marketing): array
    {
        return $this->firstColumn('EXEC usp_getshipperoverdue ?, ?, ?', [$cabang, $bulan, $marketing]);
    }

    /**
     * Data laporan dari usp_getlistshipperoverdue.
     * Kolom SP (mengandung spasi) dipetakan ke nama field grid.
     */
    public function getList(string $cabang, string $bulan, string $marketing, string $shipper, int $jumlahHari): array
    {
        $rows = $this->dbtruck->query(
            'EXEC usp_getlistshipperoverdue ?, ?, ?, ?, ?',
            [$cabang, $bulan, $marketing, $shipper, $jumlahHari]
        )->getResultArray();

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'FShipper'    => trim((string) ($r['Shipper'] ?? '')),
                'FMarketing'  => trim((string) ($r['Marketing'] ?? '')),
                'FTglInvoice' => $r['Tgl Invoice'] ?? null,
                'FTglTT'      => $r['Tgl Tanda Terima'] ?? null,
                'FNInvoice'   => trim((string) ($r['No Invoice'] ?? '')),
                'FNominal'    => (float) ($r['Nominal Invoice'] ?? 0),
                'FTop'        => (int) ($r['Top'] ?? 0),
                'FReal'       => (int) ($r['Real'] ?? 0),
                'FSelisih'    => (int) ($r['Selisih'] ?? 0),
                'FKeterangan' => trim((string) ($r['Keterangan'] ?? '')),
                'FCabang'     => trim((string) ($r['cabang'] ?? '')),
            ];
        }
        return $out;
    }
}
