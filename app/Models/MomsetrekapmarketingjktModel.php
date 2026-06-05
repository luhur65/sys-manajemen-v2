<?php
namespace App\Models;

use CodeIgniter\Model;

class MomsetrekapmarketingjktModel extends Model
{
    protected $dbtruck;
    protected $table      = 'vRekapOmsetMarketingJkt';
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
        $sql = $this->dbtruck->query("SELECT FBulan FROM vRekapOmsetMarketingJkt $whereClause");
        return $sql;
    }

    public function get($where, $sidx, $sord, $limit, $start)
    {
        $start = $start + 1;
        $sampai = $limit + $start - 1;
        
        $surut = !empty($sidx) ? $sidx : "substring(FBulan,4,4) DESC, FBulan DESC, FNMarketing";
        if (strpos($surut, 'FBulan') === false && strpos($surut, 'FNMarketing') === false) {
            $surut = $surut . ' ' . $sord;
        } else {
            // default sorting if no column clicked
            $surut = "substring(FBulan,4,4) DESC, FBulan DESC, FNMarketing ASC";
            if (!empty($sidx)) {
                $surut = $sidx . " " . $sord;
            }
        }
        
        $whereClause = trim($where) !== "" ? "WHERE 1=1 " . $where : "";

        $sql = $this->dbtruck->query("SELECT * FROM (
            SELECT FBulan, FNMarketing, FJumlahMuatan, FJumlahBongkaran, FJumlahExim, FOmset, FBiayaLapangan, ROUND(FNomPph23,0) AS FNomPph23, ROUND(FProfit,0) AS FProfit, FMargin, FTglUpdate,
            ROW_NUMBER() OVER (ORDER BY $surut) AS RowNum 
            FROM vRekapOmsetMarketingJkt 
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
            FROM vRekapOmsetMarketingJkt $whereClause");
        return $sql->getRow();
    }
    
    public function get_tglupdate()
    {
        $sql = $this->dbtruck->query("SELECT MAX(FTglUpdate) as FlastUpdate FROM vRekapOmsetMarketingJkt");
        return $sql->getResult();
    }
    
    public function getMarketingByBulan($bln)
    {
        $query = $this->dbtruck->table($this->table)
                    ->select('FNMarketing')
                    ->where('FBulan', $bln)
                    ->distinct()
                    ->get();
        return $query->getResult();
    }

    public function getMarketingByTahun($thn)
    {
        $query = $this->dbtruck->table($this->table)
                    ->select('FNMarketing')
                    ->like('FBulan', $thn, 'before')
                    ->distinct()
                    ->get();
        return $query->getResult();
    }
}
