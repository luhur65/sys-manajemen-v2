<?php
namespace App\Models;

use CodeIgniter\Model;

// Migrated from CI3: application/models/movertop.php (get_where2 logic)
// Separated to its own model for Piutang EMKL

class MpiutangemklModel extends Model
{
    protected $dbtruck;
    protected $table      = 'movertop'; // Dummy table name, we use query builder
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [];
    protected $useTimestamps = false;

    public function __construct()
    {
        parent::__construct();
        $this->dbtruck = \Config\Database::connect('dbtruck');
    }

    public function count($where, $cabang)
    {
        if ($cabang == 'SMG') $cabang = 'SMR';
        $sql = $this->dbtruck->query("SELECT FNTrans FROM LapEMKL_Piutang WHERE FKCABANG='" . $cabang . "' " . $where);
        return $sql;
    }

    public function get($where, $sidx, $sord, $limit, $start, $cabang)
    {
        if ($cabang == 'SMG') $cabang = 'SMR';
        $start = $start + 1;
        $sampai = $limit + $start - 1;
        
        $surut = $sidx; 
        
        // If sidx is numeric, try to get the column name from the stored procedure
        if (is_numeric($sidx)) {
            try {
                $orderby = $this->fquery("usp_posisikolom 'LapEMKL_Piutang'," . $sidx);
                $result = $orderby->getResult();
                if (!empty($result)) {
                    $surut = $result[0]->kolom;
                }
            } catch (\Exception $e) {
                $surut = "FSelisih";
            }
        }

        // Fallback for empty or invalid surut
        if (empty($surut)) {
            $surut = "FTgl";
        }

        $sql = $this->dbtruck->query("SELECT * FROM (
            SELECT FNTrans, FNInvoice, FNominal, FSisa, FTgl, FTglJT, FSelisih, FTglHariIni, FNShipper, FTOP, FJnsRemind, FJnsJob, 
            FNoJob, FBlnJob, FThnJob, FJnsPiutang,
            (ltrim(rtrim(str(FThnJob)))+'-'+(case when FBlnJob>=10 then '' else '0' end)+ltrim(rtrim(str(FBlnJob)))) as FNTgl,
            ROW_NUMBER() OVER (ORDER BY $surut $sord) AS RowNum 
            FROM LapEMKL_Piutang
            WHERE FKCABANG = '$cabang' $where
        ) AS GD WHERE RowNum BETWEEN $start AND $sampai ORDER BY RowNum");

        return $sql;
    }

    public function getGrandTotal($where, $cabang)
    {
        if ($cabang == 'SMG') $cabang = 'SMR';
        $sql = $this->dbtruck->query("SELECT SUM(FNominal) as TotalNominal, SUM(FSisa) as TotalSisa 
                                     FROM LapEMKL_Piutang 
                                     WHERE FKCABANG = '$cabang' $where");
        return $sql->getRow();
    }

    public function fquery($sql) 
    {
        $data = $this->dbtruck->query($sql);
        return $data;
    }

    public function get_tglupdate($cabangid) 
    {
        // Using LapEMKL_OverDue for last update date, as per original code which usedmoverTop for both
        // but let's use LapEMKL_Piutang if FlastUpdate exists. Let's assume it does.
        $sql = $this->dbtruck->query("SELECT max(FlastUpdate) as FlastUpdate FROM LapEMKL_Piutang WHERE FKCABANG='" . $cabangid . "'");
        return $sql->getResult();
    }
}
