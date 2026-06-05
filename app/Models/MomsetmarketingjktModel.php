<?php
namespace App\Models;

use CodeIgniter\Model;

class MomsetmarketingjktModel extends Model
{
    protected $dbtruck;
    protected $table      = 'OmsetMarketingJkt';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = []; 
    protected $useTimestamps = false;
    
    public function __construct()
    {
        parent::__construct();
        $this->dbtruck = \Config\Database::connect('dbtruck');
    }

    public function count($where)
    {
        $whereClause = trim($where) !== "" ? "WHERE 1=1 " . $where : "";
        $sql = $this->dbtruck->query("SELECT FTgl FROM OmsetMarketingJkt $whereClause");
        return $sql;
    }

    public function get($where, $sidx, $sord, $limit, $start)
    {
        $start = $start + 1;
        $sampai = $limit + $start - 1;
        
        $surut = !empty($sidx) ? $sidx : "FTgl";
        $whereClause = trim($where) !== "" ? "WHERE 1=1 " . $where : "";

        $sql = $this->dbtruck->query("SELECT * FROM (
            SELECT FBulan, FNMarketing, FTgl, FJumlahMuatan, FJumlahBongkaran, FJumlahExim, FOmset, FBiayaLapangan, isnull(FNomPph23,0) as FNomPph23, FProfit, FMargin, FTglUpdate,
            ROW_NUMBER() OVER (ORDER BY $surut $sord) AS RowNum 
            FROM OmsetMarketingJkt 
            $whereClause
        ) AS GD WHERE RowNum BETWEEN $start AND $sampai ORDER BY RowNum");

        return $sql;
    }

    public function getGrandTotal($where)
    {
        $whereClause = trim($where) !== "" ? "WHERE 1=1 " . $where : "";
        $sql = $this->dbtruck->query("SELECT 
            SUM(CAST(FJumlahMuatan AS float)) as TotalMuatan, 
            SUM(CAST(FJumlahBongkaran AS float)) as TotalBongkaran, 
            SUM(CAST(FJumlahExim AS float)) as TotalExim, 
            SUM(CAST(FOmset AS float)) as TotalOmset, 
            SUM(CAST(FBiayaLapangan AS float)) as TotalBiayaLapangan, 
            SUM(CAST(isnull(FNomPph23,0) AS float)) as TotalPph23, 
            SUM(CAST(FProfit AS float)) as TotalProfit 
            FROM OmsetMarketingJkt $whereClause");
        return $sql->getRow();
    }
    
    public function get_tglupdate()
    {
        $sql = $this->dbtruck->query("SELECT MAX(FTglUpdate) as FlastUpdate FROM OmsetMarketingJkt");
        return $sql->getResult();
    }
}
