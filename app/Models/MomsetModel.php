<?php
namespace App\Models;

use CodeIgniter\Model;

class MomsetModel extends Model
{
    protected $dbtruck;
    protected $table      = 'momset';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = []; 
    protected $useTimestamps = false;
    
    public function __construct()
    {
        parent::__construct();
        $this->dbtruck = \Config\Database::connect('dbtruck');
    }
    
    private function getTableName($cabang)
    {
        $cabangs = [
            'MDN' => 'Mdn',
            'JKT' => 'Jkt',
            'SBY' => 'Sby',
            'MKS' => 'Mks',
            'SMG' => 'Smg',
            'BTG' => 'Btg'
        ];
        $suffix = isset($cabangs[$cabang]) ? $cabangs[$cabang] : 'Mdn';
        return 'Omset' . $suffix;
    }

    public function count($where, $cabang)
    {
        $table = $this->getTableName($cabang);
        $whereClause = trim($where) !== "" ? "WHERE 1=1 " . $where : "";
        $sql = $this->dbtruck->query("SELECT FTgl FROM $table $whereClause");
        return $sql;
    }

    public function get($where, $sidx, $sord, $limit, $start, $cabang)
    {
        $start = $start + 1;
        $sampai = $limit + $start - 1;
        $table = $this->getTableName($cabang);
        
        $surut = !empty($sidx) ? $sidx : "FTgl";
        $whereClause = trim($where) !== "" ? "WHERE 1=1 " . $where : "";

        $sql = $this->dbtruck->query("SELECT * FROM (
            SELECT FBulan, FTgl, FJumlahMuatan, FJumlahBongkaran, FJumlahExim, FOmset, FBiayaLapangan, FNomPph23, FProfit, FMargin, FTglUpdate,
            ROW_NUMBER() OVER (ORDER BY $surut $sord) AS RowNum 
            FROM $table 
            $whereClause
        ) AS GD WHERE RowNum BETWEEN $start AND $sampai ORDER BY RowNum");

        return $sql;
    }

    public function getGrandTotal($where, $cabang)
    {
        $table = $this->getTableName($cabang);
        $whereClause = trim($where) !== "" ? "WHERE 1=1 " . $where : "";
        $sql = $this->dbtruck->query("SELECT 
            SUM(CAST(FJumlahMuatan AS float)) as TotalMuatan, 
            SUM(CAST(FJumlahBongkaran AS float)) as TotalBongkaran, 
            SUM(CAST(FJumlahExim AS float)) as TotalExim, 
            SUM(CAST(FOmset AS float)) as TotalOmset, 
            SUM(CAST(FBiayaLapangan AS float)) as TotalBiayaLapangan, 
            SUM(CAST(FNomPph23 AS float)) as TotalPph23, 
            SUM(CAST(FProfit AS float)) as TotalProfit 
            FROM $table $whereClause");
        return $sql->getRow();
    }
    
    public function get_tglupdate($cabang)
    {
        $table = $this->getTableName($cabang);
        $sql = $this->dbtruck->query("SELECT MAX(FTglUpdate) as FlastUpdate FROM $table");
        return $sql->getResult();
    }

    // Keep old methods just in case they are called from elsewhere (e.g., another controller)
    public function get_whereMDN($where) { return $this->dbtruck->query("SELECT * FROM OmsetMdn " . $where); }
    public function get_whereJKT($where) { return $this->dbtruck->query("SELECT * FROM OmsetJkt " . $where); }
    public function get_whereSBY($where) { return $this->dbtruck->query("SELECT * FROM OmsetSby " . $where); }
    public function get_whereMKS($where) { return $this->dbtruck->query("SELECT * FROM OmsetMks " . $where); }
    public function get_whereSMG($where) { return $this->dbtruck->query("SELECT * FROM OmsetSmg " . $where); }
    
    public function fquery($sql)
    {
        $data = $this->dbtruck->query($sql);
        return $data;
    }
}
