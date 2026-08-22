<?php

namespace App\Models;

use CodeIgniter\Model;

class MtruckingtradoluarModel extends Model
{
    protected $dbtruck;

    public function __construct()
    {
        parent::__construct();
        $this->dbtruck = \Config\Database::connect('dbtruck');
    }

    /**
     * Whitelist kode cabang -> sufiks nama tabel.
     * Nama tabel tidak bisa dibinding sebagai parameter, jadi nilainya
     * wajib berasal dari daftar tetap ini, bukan dari input POST.
     */
    private const CABANG_SUFFIX = [
        'MDN' => 'Mdn',
        'JKT' => 'Jkt',
        'SBY' => 'Sby',
        'MKS' => 'Mks',
    ];

    private function getTableName($cabang, string $prefix = 'TradoLuar'): string
    {
        $kode = is_string($cabang) ? strtoupper(trim($cabang)) : '';

        if (! isset(self::CABANG_SUFFIX[$kode])) {
            log_message('warning', '[Trado Luar] Kode cabang tidak dikenal ditolak: ' . $kode);
            $kode = 'MDN';
        }

        return $prefix . self::CABANG_SUFFIX[$kode];
    }

    public function get_whereMDN($where = "")
    {
        $whereClause = trim($where) !== "" ? "WHERE 1=1 " . $where : "";
        $sql = $this->dbtruck->query("SELECT * FROM TradoLuarMdn $whereClause");
        return $sql;
    }

    public function get_whereJKT($where = "")
    {
        $whereClause = trim($where) !== "" ? "WHERE 1=1 " . $where : "";
        $sql = $this->dbtruck->query("SELECT * FROM TradoLuarJkt $whereClause");
        return $sql;
    }

    public function get_whereSBY($where = "")
    {
        $whereClause = trim($where) !== "" ? "WHERE 1=1 " . $where : "";
        $sql = $this->dbtruck->query("SELECT * FROM TradoLuarSby $whereClause");
        return $sql;
    }

    public function get_whereMKS($where = "")
    {
        $whereClause = trim($where) !== "" ? "WHERE 1=1 " . $where : "";
        $sql = $this->dbtruck->query("SELECT * FROM TradoLuarMks $whereClause");
        return $sql;
    }
    public function count($where, $cabang)
    {
        $tableName = $this->getTableName($cabang);
        $whereClause = trim($where) !== "" ? "WHERE 1=1 " . $where : "";
        return $this->dbtruck->query("SELECT FTgl FROM $tableName $whereClause");
    }

    public function get($where, $sidx, $sord, $limit, $start, $cabang)
    {
        $tableName = $this->getTableName($cabang);
        $start = $start + 1;
        $sampai = $limit + $start - 1;
        $surut = $sidx . " " . $sord;
        $whereClause = trim($where) !== "" ? "WHERE 1=1 " . $where : "";

        return $this->dbtruck->query("SELECT * FROM (
            SELECT *, ROW_NUMBER() OVER (ORDER BY $surut) AS RowNum 
            FROM $tableName 
            $whereClause
        ) AS GD WHERE RowNum BETWEEN $start AND $sampai ORDER BY RowNum");
    }

    public function getGrandTotal($where, $cabang)
    {
        $tableName = $this->getTableName($cabang);
        $whereClause = trim($where) !== "" ? "WHERE 1=1 " . $where : "";
        $sql = $this->dbtruck->query("SELECT 
            SUM(ISNULL(TRY_CAST(REPLACE(FUkuran20Muatan, ',', '') AS FLOAT), 0)) as Total20Muatan,
            SUM(ISNULL(TRY_CAST(REPLACE(FUkuran2x20Muatan, ',', '') AS FLOAT), 0)) as Total2x20Muatan,
            SUM(ISNULL(TRY_CAST(REPLACE(FUkuran40Muatan, ',', '') AS FLOAT), 0)) as Total40Muatan,
            SUM(ISNULL(TRY_CAST(REPLACE(FUkuran20Bongkaran, ',', '') AS FLOAT), 0)) as Total20Bongkaran,
            SUM(ISNULL(TRY_CAST(REPLACE(FUkuran2x20Bongkaran, ',', '') AS FLOAT), 0)) as Total2x20Bongkaran,
            SUM(ISNULL(TRY_CAST(REPLACE(FUkuran40Bongkaran, ',', '') AS FLOAT), 0)) as Total40Bongkaran,
            SUM(ISNULL(TRY_CAST(REPLACE(FUkuran20Import, ',', '') AS FLOAT), 0)) as Total20Import,
            SUM(ISNULL(TRY_CAST(REPLACE(FUkuran2x20Import, ',', '') AS FLOAT), 0)) as Total2x20Import,
            SUM(ISNULL(TRY_CAST(REPLACE(FUkuran40Import, ',', '') AS FLOAT), 0)) as Total40Import,
            SUM(ISNULL(TRY_CAST(REPLACE(FUkuran20Eksport, ',', '') AS FLOAT), 0)) as Total20Eksport,
            SUM(ISNULL(TRY_CAST(REPLACE(FUkuran2x20Eksport, ',', '') AS FLOAT), 0)) as Total2x20Eksport,
            SUM(ISNULL(TRY_CAST(REPLACE(FUkuran40Eksport, ',', '') AS FLOAT), 0)) as Total40Eksport
            FROM $tableName $whereClause");
        return $sql->getRow();
    }

    public function get_tglupdate($cabang)
    {
        $tableName = $this->getTableName($cabang);
        $sql = $this->dbtruck->query("SELECT FTglUpdate FROM $tableName ORDER BY FTglUpdate DESC OFFSET 0 ROWS FETCH NEXT 1 ROWS ONLY");
        return $sql->getResult();
    }

    public function countDetail($where, $cabang)
    {
        $tableName = $this->getTableName($cabang, 'TradoLuarDetail');
        $whereClause = trim($where) !== "" ? "WHERE 1=1 " . $where : "";
        return $this->dbtruck->query("SELECT FNTrans FROM $tableName $whereClause");
    }

    public function getDetail($where, $sidx, $sord, $limit, $start, $cabang)
    {
        $tableName = $this->getTableName($cabang, 'TradoLuarDetail');
        $start = $start + 1;
        $sampai = $limit + $start - 1;
        $surut = $sidx . " " . $sord;
        $whereClause = trim($where) !== "" ? "WHERE 1=1 " . $where : "";

        return $this->dbtruck->query("SELECT * FROM (
            SELECT *, ROW_NUMBER() OVER (ORDER BY $surut) AS RowNum 
            FROM $tableName 
            $whereClause
        ) AS GD WHERE RowNum BETWEEN $start AND $sampai ORDER BY RowNum");
    }

    public function getGrandTotalDetail($where, $cabang)
    {
        $tableName = $this->getTableName($cabang, 'TradoLuarDetail');
        $whereClause = trim($where) !== "" ? "WHERE 1=1 " . $where : "";
        $sql = $this->dbtruck->query("SELECT 
            SUM(ISNULL(TRY_CAST(REPLACE(FNominalHargaTrucking, ',', '') AS FLOAT), 0)) as TotalHargaTrucking,
            SUM(ISNULL(TRY_CAST(REPLACE(FNominalHargaTruckingPusat, ',', '') AS FLOAT), 0)) as TotalHargaTruckingPusat,
            SUM(ISNULL(TRY_CAST(REPLACE(FSelisih, ',', '') AS FLOAT), 0)) as TotalSelisih,
            SUM(CASE WHEN LOWER(FOrderan) LIKE '%muat%' THEN 1 ELSE 0 END) as TotalMuatan,
            SUM(CASE WHEN LOWER(FOrderan) LIKE '%bongkar%' THEN 1 ELSE 0 END) as TotalBongkaran,
            SUM(CASE WHEN LOWER(FOrderan) LIKE '%import%' THEN 1 ELSE 0 END) as TotalImport,
            SUM(CASE WHEN LOWER(FOrderan) LIKE '%ekspor%' THEN 1 ELSE 0 END) as TotalEksport
            FROM $tableName $whereClause");
        return $sql->getRow();
    }
}
