<?php
namespace App\Models;

use CodeIgniter\Model;

// Migrated from CI3: application/models/movertop.php

class MovertopmarketingModel extends Model
{
    protected $dbtruck;
    protected $table      = 'movertop';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = []; // TODO: Add allowed fields
    protected $useTimestamps = false;
    public function __construct()
    {
        parent::__construct();
        $this->dbtruck = \Config\Database::connect('dbtruck');
    }
    public function count($where,$cabang){
        if ($cabang == 'SMG') $cabang = 'SMR';
		$sql = $this->dbtruck->query("SELECT FNTrans FROM LapEMKL_OverDue WHERE FKCABANG='".$cabang."' ". $where);

        return $sql;

	}
    public function get_where1($where){
		$sql = $this->dbtruck->query("SELECT FNTrans , FNInvoice , FNominal , FSisa , FTgl , FTglJT , FSelisih , FTglHariIni , FNShipper , FTOP , FJnsRemind, FNoJob, FBlnJob, FThnJob, FJnsJob,(ltrim(rtrim(str(FThnJob)))+'-'+(case when FBlnJob>=10 then '' else '0' end)+ltrim(rtrim(str(FBlnJob)))) as FNTgl, FJnsPiutang FROM LapEMKL_OverDue
			WHERE FKCABANG ='".$where."' ORDER BY FSelisih desc");

		return $sql;
	}
    public function get_where2($where){
		$sql = $this->dbtruck->query("SELECT FNTrans , FNInvoice , FNominal , FSisa , FTgl , FTglJT , FSelisih , FTglHariIni , FNShipper , FTOP , FJnsRemind, FNoJob, FBlnJob, FThnJob, FJnsJob,(ltrim(rtrim(str(FThnJob)))+'-'+(case when FBlnJob>=10 then '' else '0' end)+ltrim(rtrim(str(FBlnJob)))) as FNTgl, FJnsPiutang FROM LapEMKL_Piutang
			WHERE FKCABANG ='".$where."' ORDER BY FSelisih desc");

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
                $orderby = $this->fquery("usp_posisikolom 'LapEMKL_OverDue'," . $sidx);
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
            SELECT FNMarketing, FNTrans, FNInvoice, FNominal, FSisa, FTgl, FTglJT, FSelisih, FTglHariIni, FNShipper, FTOP, FJnsRemind, FJnsJob, 
            FNoJob, FBlnJob, FThnJob, FJnsPiutang,
            (ltrim(rtrim(str(FThnJob)))+'-'+(case when FBlnJob>=10 then '' else '0' end)+ltrim(rtrim(str(FBlnJob)))) as FNTgl,
            ROW_NUMBER() OVER (ORDER BY $surut $sord) AS RowNum 
            FROM LapEMKL_OverDue
            WHERE FKCABANG = '$cabang' $where
        ) AS GD WHERE RowNum BETWEEN $start AND $sampai ORDER BY RowNum");

        return $sql;
    }
    public function get_where($where){
		$sql = $this->dbtruck->query("SELECT FNTrans , FNInvoice , FNominal , FSisa , FTgl , FTglJT , FSelisih , FTglHariIni , FNShipper , FTOP , FIDRelasi , FKCabang , FJnsRemind, FJnsJob FROM LapEMKL_OverDue " . $where);

		return $sql;
	}
    public function getGrandTotal($where, $cabang)
    {
        if ($cabang == 'SMG') $cabang = 'SMR';
        $sql = $this->dbtruck->query("SELECT SUM(FNominal) as TotalNominal, SUM(FSisa) as TotalSisa 
                                     FROM LapEMKL_OverDue 
                                     WHERE FKCABANG = '$cabang' $where");
        return $sql->getRow();
    }

    public function fquery($sql) {
		$data = $this->dbtruck->query($sql);

		return $data;
	}

    public function get_marketing($where){
		$sql = $this->dbtruck->query("SELECT DISTINCT FNMarketing FROM LapEMKL_OverDue WHERE FKCABANG ='".$where."'");
		$hasil = '<option value="">ALL</option>';
		foreach ($sql->getResult() as $key) {
		 	$hasil = $hasil.'<option value="'.$key->FNMarketing.'">'.$key->FNMarketing.'</option>';
		 }
		return $hasil;
	}
    public function get_tglupdate($cabangid) {
		$sql = $this->dbtruck->query("SELECT max(FlastUpdate) as FlastUpdate FROM LapEMKL_OverDue WHERE FKCABANG='" . $cabangid."'");

		return $sql->getResult();
	}
}
